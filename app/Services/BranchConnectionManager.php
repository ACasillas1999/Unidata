<?php

namespace App\Services;

use App\Models\Branch;
use Closure;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Config;
use Throwable;

class BranchConnectionManager
{
    public function __construct(
        protected DatabaseManager $database,
    ) {
    }

    public function localConnectionName(): string
    {
        return config('branch-connections.local_connection', config('database.default'));
    }

    public function getActiveBranches(): Collection
    {
        return Branch::query()
            ->active()
            ->orderBy('name')
            ->get();
    }

    public function findBranchOrFail(int|string|Branch $branch): Branch
    {
        if ($branch instanceof Branch) {
            return $branch;
        }

        $query = Branch::query();

        if (is_int($branch) || ctype_digit((string) $branch)) {
            $resolved = $query->find((int) $branch);
        } else {
            $resolved = $query->where('code', $branch)->first();
        }

        if (! $resolved) {
            throw (new ModelNotFoundException())->setModel(Branch::class, [$branch]);
        }

        return $resolved;
    }

    public function getConnectionName(int|string|Branch $branch): string
    {
        $branch = $this->findBranchOrFail($branch);

        return config('branch-connections.connection_prefix', 'branch_').$branch->getKey();
    }

    public function connect(int|string|Branch $branch): ConnectionInterface
    {
        $branch = $this->findBranchOrFail($branch);
        $connectionName = $this->getConnectionName($branch);

        Config::set("database.connections.{$connectionName}", $this->makeConnectionConfig($branch));

        $this->database->purge($connectionName);

        try {
            $connection = $this->database->connection($connectionName);
            $connection->getPdo();

            $this->updateConnectionStatus($branch, 'connected');

            return $connection;
        } catch (Throwable $exception) {
            $this->updateConnectionStatus($branch, 'error');
            throw $exception;
        }
    }

    public function disconnect(int|string|Branch $branch): void
    {
        $connectionName = $this->getConnectionName($branch);

        $this->database->disconnect($connectionName);
        $this->database->purge($connectionName);
        Config::set("database.connections.{$connectionName}", null);
    }

    public function testConnection(int|string|Branch $branch): bool
    {
        try {
            $this->connect($branch);
            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public function using(int|string|Branch $branch, Closure $callback): mixed
    {
        $resolvedBranch = $this->findBranchOrFail($branch);
        $connection = $this->connect($resolvedBranch);

        return $callback($connection, $resolvedBranch);
    }

    protected function makeConnectionConfig(Branch $branch): array
    {
        return array_merge(
            config('branch-connections.template', []),
            [
                'host' => $branch->db_host,
                'port' => $branch->db_port ?: config('branch-connections.template.port', '3306'),
                'database' => $branch->db_database,
                'username' => $branch->db_user,
                'password' => $branch->db_password,
            ],
        );
    }

    protected function updateConnectionStatus(Branch $branch, string $connectionStatus): void
    {
        $branch->forceFill([
            'connection_status' => $connectionStatus,
            'last_connection_check' => now(),
        ])->save();
    }
}
