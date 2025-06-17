<?php

namespace App\Services;

use App\Models\Workflow;
use App\Models\WorkflowExecution;
use App\Models\WorkflowExecutionLog;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Exception;

class WorkflowService
{
    protected AiModelService $aiModelService;
    protected KnowledgeBaseService $knowledgeBaseService;
    protected MemoryService $memoryService;

    public function __construct(
        AiModelService $aiModelService,
        KnowledgeBaseService $knowledgeBaseService,
        MemoryService $memoryService
    ) {
        $this->aiModelService = $aiModelService;
        $this->knowledgeBaseService = $knowledgeBaseService;
        $this->memoryService = $memoryService;
    }

    /**
     * Create a new workflow.
     */
    public function createWorkflow(User $user, array $data): Workflow
    {
        // Validate workflow definition
        $errors = $this->validateWorkflowDefinition($data['definition'] ?? []);
        if (!empty($errors)) {
            throw new Exception('Workflow validation failed: ' . implode(', ', $errors));
        }

        $workflow = Workflow::create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'definition' => $data['definition'],
            'version' => $data['version'] ?? '1.0.0',
            'status' => $data['status'] ?? Workflow::STATUS_DRAFT,
            'settings' => $data['settings'] ?? [],
            'variables' => $data['variables'] ?? [],
            'is_public' => $data['is_public'] ?? false,
            'is_template' => $data['is_template'] ?? false,
            'category' => $data['category'] ?? null,
            'tags' => $data['tags'] ?? [],
        ]);

        Log::info('Workflow created', [
            'workflow_id' => $workflow->id,
            'user_id' => $user->id,
            'name' => $workflow->name,
        ]);

        return $workflow;
    }

    /**
     * Execute a workflow.
     */
    public function executeWorkflow(
        Workflow $workflow,
        User $user,
        array $inputData = [],
        array $variables = []
    ): WorkflowExecution {
        // Validate workflow is executable
        if ($workflow->status !== Workflow::STATUS_ACTIVE) {
            throw new Exception('Workflow is not active');
        }

        // Create execution record
        $execution = $workflow->execute($inputData, $variables);

        try {
            // Start execution
            $execution->start();

            // Execute workflow nodes
            $result = $this->executeWorkflowNodes($execution);

            // Complete execution
            $execution->complete($result);

            Log::info('Workflow execution completed', [
                'execution_id' => $execution->execution_id,
                'workflow_id' => $workflow->id,
            ]);

        } catch (Exception $e) {
            $execution->fail($e->getMessage());

            Log::error('Workflow execution failed', [
                'execution_id' => $execution->execution_id,
                'workflow_id' => $workflow->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        return $execution;
    }

    /**
     * Get workflow execution status.
     */
    public function getExecutionStatus(string $executionId): array
    {
        $execution = WorkflowExecution::where('execution_id', $executionId)->first();

        if (!$execution) {
            throw new Exception('Execution not found');
        }

        $logs = $execution->logs()->orderBy('created_at')->get();

        return [
            'execution_id' => $execution->execution_id,
            'status' => $execution->status,
            'progress' => $execution->getProgressPercentage(),
            'completed_nodes' => $execution->completed_nodes,
            'failed_nodes' => $execution->failed_nodes,
            'total_nodes' => $execution->total_nodes,
            'started_at' => $execution->started_at,
            'completed_at' => $execution->completed_at,
            'duration' => $execution->getDurationForHumans(),
            'total_cost' => $execution->total_cost,
            'logs' => $logs->map(function ($log) {
                return [
                    'node_id' => $log->node_id,
                    'node_type' => $log->node_type,
                    'status' => $log->status,
                    'started_at' => $log->started_at,
                    'completed_at' => $log->completed_at,
                    'duration_ms' => $log->execution_time_ms,
                    'cost' => $log->cost,
                    'error' => $log->error_message,
                ];
            }),
        ];
    }

    /**
     * Cancel a workflow execution.
     */
    public function cancelExecution(string $executionId): void
    {
        $execution = WorkflowExecution::where('execution_id', $executionId)->first();

        if (!$execution) {
            throw new Exception('Execution not found');
        }

        if (!$execution->isRunning()) {
            throw new Exception('Execution is not running');
        }

        $execution->cancel();

        Log::info('Workflow execution cancelled', [
            'execution_id' => $executionId,
        ]);
    }

    /**
     * Get workflow templates.
     */
    public function getTemplates(int $limit = 20): Collection
    {
        return Workflow::templates()
            ->active()
            ->orderBy('execution_count', 'desc')
            ->take($limit)
            ->get();
    }

    /**
     * Clone a workflow from template.
     */
    public function cloneFromTemplate(Workflow $template, User $user, array $customizations = []): Workflow
    {
        if (!$template->is_template) {
            throw new Exception('Workflow is not a template');
        }

        $data = [
            'name' => $customizations['name'] ?? $template->name . ' (Copy)',
            'description' => $customizations['description'] ?? $template->description,
            'definition' => $customizations['definition'] ?? $template->definition,
            'settings' => array_merge($template->settings ?? [], $customizations['settings'] ?? []),
            'variables' => array_merge($template->variables ?? [], $customizations['variables'] ?? []),
            'category' => $customizations['category'] ?? $template->category,
            'tags' => $customizations['tags'] ?? $template->tags,
        ];

        return $this->createWorkflow($user, $data);
    }

    /**
     * Execute workflow nodes.
     */
    protected function executeWorkflowNodes(WorkflowExecution $execution): array
    {
        $workflow = $execution->workflow;
        $definition = $workflow->definition;
        $nodes = $definition['nodes'] ?? [];
        $edges = $definition['edges'] ?? [];

        $nodeResults = [];
        $executedNodes = [];
        $completedNodes = 0;

        // Find start nodes (nodes with no incoming edges)
        $startNodes = $this->findStartNodes($nodes, $edges);

        foreach ($startNodes as $node) {
            $this->executeNode($execution, $node, $nodeResults, $executedNodes, $completedNodes);
        }

        return $nodeResults;
    }

    /**
     * Execute a single node.
     */
    protected function executeNode(
        WorkflowExecution $execution,
        array $node,
        array &$nodeResults,
        array &$executedNodes,
        int &$completedNodes
    ): void {
        $nodeId = $node['id'];

        if (in_array($nodeId, $executedNodes)) {
            return; // Already executed
        }

        $startTime = microtime(true);

        // Create execution log
        $log = WorkflowExecutionLog::create([
            'workflow_execution_id' => $execution->id,
            'node_id' => $nodeId,
            'node_type' => $node['type'],
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            // Execute node based on type
            $result = $this->executeNodeByType($execution, $node, $nodeResults);

            $endTime = microtime(true);
            $executionTime = (int)(($endTime - $startTime) * 1000);

            // Update log
            $log->update([
                'status' => 'completed',
                'output_data' => $result,
                'completed_at' => now(),
                'execution_time_ms' => $executionTime,
                'cost' => $result['cost'] ?? 0,
            ]);

            // Store result
            $nodeResults[$nodeId] = $result;
            $executedNodes[] = $nodeId;
            $completedNodes++;

            // Update execution progress
            $execution->updateProgress($completedNodes);

            // Add cost to execution
            if (isset($result['cost'])) {
                $execution->addCost($result['cost']);
            }

        } catch (Exception $e) {
            $endTime = microtime(true);
            $executionTime = (int)(($endTime - $startTime) * 1000);

            // Update log with error
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
                'execution_time_ms' => $executionTime,
            ]);

            // Update execution with failed node
            $execution->updateProgress($completedNodes, $execution->failed_nodes + 1);

            throw $e;
        }
    }

    /**
     * Execute node by type.
     */
    protected function executeNodeByType(
        WorkflowExecution $execution,
        array $node,
        array $nodeResults
    ): array {
        $nodeType = $node['type'];
        $nodeConfig = $node['config'] ?? [];

        switch ($nodeType) {
            case 'ai_call':
                return $this->executeAiCallNode($execution, $nodeConfig, $nodeResults);

            case 'knowledge_search':
                return $this->executeKnowledgeSearchNode($execution, $nodeConfig, $nodeResults);

            case 'memory_store':
                return $this->executeMemoryStoreNode($execution, $nodeConfig, $nodeResults);

            case 'memory_retrieve':
                return $this->executeMemoryRetrieveNode($execution, $nodeConfig, $nodeResults);

            case 'condition':
                return $this->executeConditionNode($execution, $nodeConfig, $nodeResults);

            case 'data_transform':
                return $this->executeDataTransformNode($execution, $nodeConfig, $nodeResults);

            default:
                throw new Exception("Unknown node type: {$nodeType}");
        }
    }

    /**
     * Execute AI call node.
     */
    protected function executeAiCallNode(
        WorkflowExecution $execution,
        array $config,
        array $nodeResults
    ): array {
        $model = $this->aiModelService->getDefaultModel();
        $prompt = $this->resolveVariables($config['prompt'] ?? '', $execution->variables, $nodeResults);

        $response = $this->aiModelService->callModel(
            $model,
            $execution->user,
            $prompt,
            $config['parameters'] ?? [],
            "workflow_{$execution->workflow_id}"
        );

        return [
            'response' => $response['content'] ?? '',
            'tokens_used' => $response['usage']['total_tokens'] ?? 0,
            'cost' => $response['cost'] ?? 0,
        ];
    }

    /**
     * Execute knowledge search node.
     */
    protected function executeKnowledgeSearchNode(
        WorkflowExecution $execution,
        array $config,
        array $nodeResults
    ): array {
        $knowledgeBaseId = $config['knowledge_base_id'];
        $query = $this->resolveVariables($config['query'] ?? '', $execution->variables, $nodeResults);

        // This would search the knowledge base
        return [
            'results' => [],
            'sources' => [],
        ];
    }

    /**
     * Execute memory store node.
     */
    protected function executeMemoryStoreNode(
        WorkflowExecution $execution,
        array $config,
        array $nodeResults
    ): array {
        $key = $this->resolveVariables($config['key'] ?? '', $execution->variables, $nodeResults);
        $value = $this->resolveVariables($config['value'] ?? '', $execution->variables, $nodeResults);
        $memoryType = $config['memory_type'] ?? 'working';

        $this->memoryService->store(
            $execution->user,
            $memoryType,
            $key,
            $value,
            "workflow_{$execution->workflow_id}"
        );

        return ['success' => true];
    }

    /**
     * Execute memory retrieve node.
     */
    protected function executeMemoryRetrieveNode(
        WorkflowExecution $execution,
        array $config,
        array $nodeResults
    ): array {
        $key = $this->resolveVariables($config['key'] ?? '', $execution->variables, $nodeResults);
        $memoryType = $config['memory_type'] ?? 'working';

        $memory = $this->memoryService->retrieve(
            $execution->user,
            $memoryType,
            $key,
            "workflow_{$execution->workflow_id}"
        );

        return [
            'value' => $memory ? $memory->getDecodedValue() : null,
            'found' => $memory !== null,
        ];
    }

    /**
     * Execute condition node.
     */
    protected function executeConditionNode(
        WorkflowExecution $execution,
        array $config,
        array $nodeResults
    ): array {
        $condition = $this->resolveVariables($config['condition'] ?? '', $execution->variables, $nodeResults);
        
        // Simple condition evaluation - in production, use a proper expression evaluator
        $result = eval("return {$condition};");

        return ['result' => $result];
    }

    /**
     * Execute data transform node.
     */
    protected function executeDataTransformNode(
        WorkflowExecution $execution,
        array $config,
        array $nodeResults
    ): array {
        $inputData = $this->resolveVariables($config['input_data'] ?? '', $execution->variables, $nodeResults);
        $transformType = $config['transform_type'] ?? 'json_parse';

        switch ($transformType) {
            case 'json_parse':
                $outputData = json_decode($inputData, true);
                break;
            case 'json_encode':
                $outputData = json_encode($inputData);
                break;
            default:
                $outputData = $inputData;
        }

        return ['output_data' => $outputData];
    }

    /**
     * Find start nodes in the workflow.
     */
    protected function findStartNodes(array $nodes, array $edges): array
    {
        $nodeIds = array_column($nodes, 'id');
        $targetNodes = array_column($edges, 'target');
        
        $startNodeIds = array_diff($nodeIds, $targetNodes);
        
        return array_filter($nodes, function ($node) use ($startNodeIds) {
            return in_array($node['id'], $startNodeIds);
        });
    }

    /**
     * Resolve variables in a string.
     */
    protected function resolveVariables(string $text, array $variables, array $nodeResults): string
    {
        // Replace workflow variables
        foreach ($variables as $key => $value) {
            $text = str_replace("{{$key}}", $value, $text);
        }

        // Replace node results
        foreach ($nodeResults as $nodeId => $result) {
            if (is_array($result)) {
                foreach ($result as $resultKey => $resultValue) {
                    $text = str_replace("{{$nodeId.$resultKey}}", $resultValue, $text);
                }
            }
        }

        return $text;
    }

    /**
     * Validate workflow definition.
     */
    protected function validateWorkflowDefinition(array $definition): array
    {
        $errors = [];

        if (!isset($definition['nodes']) || !is_array($definition['nodes'])) {
            $errors[] = 'Workflow must have nodes array';
        }

        if (!isset($definition['edges']) || !is_array($definition['edges'])) {
            $errors[] = 'Workflow must have edges array';
        }

        // Additional validation logic would go here

        return $errors;
    }
}
