<?php

namespace App\Repositories;

use App\Models\Domain;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class DomainRepository
{
    protected $model;
    

    public function __construct(Domain $domain)
    {
        $this->model = $domain;
    }
    
    public function all(): Collection
    {
        return $this->model->all();
    }
    
    public function create(array $data): Domain
    {
        if(Auth::check() && !isset($data['insert_user'])) {
            $data['insert_user'] = Auth::user()->user_uuid;
        }
        return $this->model->create($data);
    }
    
    public function update(Domain $domain, array $data): bool
    {
        if(Auth::check() && !isset($data['update_user'])) {
            $data['update_user'] = Auth::user()->user_uuid;
        }
        return $domain->update($data);
    }
    
    public function delete(Domain $domain): ?bool
    {
        return $domain->delete();
    }
    
    public function findByUuid(string $uuid, bool $enabled_only = true): ?Domain
    {
        $query = $this->model->where('domain_uuid', $uuid);
        
        if ($enabled_only) {
            $query->where('domain_enabled', 'true');
        }
        
        return $query->first();
    }
    
    public function existsByUuid(string $uuid, bool $enabled_only = true): bool
    {
        $query = $this->model->where('domain_uuid', $uuid);
        
        if ($enabled_only) {
            $query->where('domain_enabled', 'true');
        }
        
        return ($query->count() > 0);
    }
    

    public function getForSelectControl(): mixed
    {
        $domains = [];

        if (Auth::check()) {
            $db_type = DB::getConfig("driver");
            $sql = "WITH RECURSIVE children AS (
                        SELECT d.domain_uuid, d.domain_parent_uuid, d.domain_name, " . ($db_type == 'pgsql' ? "CAST(d.domain_enabled AS text)" : "d.domain_enabled") . ", d.domain_description, CAST('' AS CHAR(255)) AS parent_domain_name, 1 AS depth, domain_name AS path, (SELECT COUNT(*) FROM " . Domain::getTableName() . " d1 WHERE d1.domain_parent_uuid = d.domain_uuid) AS kids FROM " . Domain::getTableName() . " d
                        WHERE ";

            if (Auth::user()->hasPermission('domain_select')) {
                // if permission domain_select
                $sql .= "d.domain_parent_uuid IS null OR NOT exists (SELECT 1 FROM " . Domain::getTableName() . " t1 WHERE d.domain_parent_uuid = t1.domain_uuid) ";
            } else {
                // if NOT permission domain_select
                $sql .= "domain_uuid = '" . Session::get('domain_uuid') . "' ";
            }

            $sql .= "UNION
            SELECT tp.domain_uuid, tp.domain_parent_uuid, tp.domain_name, " . ($db_type == 'pgsql' ? "CAST(tp.domain_enabled AS text)" : "tp.domain_enabled") . ", tp.domain_description, c.domain_name AS parent_domain_name, depth + 1, CONCAT(path,';',tp.domain_name), (SELECT count(*) from " . Domain::getTableName() . " d1 where d1.domain_parent_uuid = tp.domain_uuid) AS kids FROM " . Domain::getTableName() . " tp
                    JOIN children c ON tp.domain_parent_uuid = c.domain_uuid ) SELECT * FROM children ";

            if (App::hasDebugModeEnabled()) {
                Log::debug('[DomainRepository:getForSelectControl] $sql: ' . $sql);
            }

            $domains = DB::select($sql);
        }

        return $domains;
    }
}