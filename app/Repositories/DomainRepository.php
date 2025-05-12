<?php

namespace App\Repositories;

use App\Facades\Setting;
use App\Models\Dialplan;
use App\Models\DialplanDetail;
use App\Models\Domain;
use App\Repositories\dialplanRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class DomainRepository
{
    protected $model;
    protected $domainRepository;
    protected $dialplanRepository;

    public function __construct(Domain $domain, DomainRepository $domainRepository, dialplanRepository $dialplanRepository)
    {
        $this->model = $domain;
        $this->domainRepository = $domainRepository;
        $this->dialplanRepository = $dialplanRepository;
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
        $newDomain = $this->model->create($data);
        $this->importXML($newDomain->domain_uuid);
        return $newDomain;
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

    public function importXML(string $new_dommain_uuid)
    {
        $newDomain = Domain::find($new_dommain_uuid);
        $dialplanStorage = Storage::build([
                    'driver' => 'local',
                    'root' => resource_path('dialplans'),
                ]);
        $dialplanFiles = array_filter($dialplanStorage->files(), function ($file)
                        {
                            return preg_match('/\.xml$/i', $file);
                        });
        natsort($dialplanFiles);

        $dialplan_query = $this->model::where(function($query) use ($new_dommain_uuid){
                        return $query->where('domain_uuid','=', $new_dommain_uuid)
                                ->orWhereNull('domain_uuid');
                        })
                ->whereNotNull('app_uuid');
        if(App::hasDebugModeEnabled())
        {
            Log::notice('['.__FILE__.':'.__LINE__.']['.__CLASS__.']['.__METHOD__.'] $dialplans_query: '.$dialplans_query->toRawSql());
        }
        $dialplans = $dialplan_query->get();
        $x = 0; $array = [];

        foreach ($dialplanFiles as $dialplanFile)
        {
            $xmlString = $dialplanStorage::get($dialplanFile);
            if (!empty($xmlString))
            {
                $lenght = Setting::getDefaultSetting('security', 'pin_lenght', 'var') ?? 8;
                $newPin = str_pad(rand(0, pow(10, $lenght) - 1),$lenght, '0', STR_PAD_LEFT);
                $xmlString = str_replace("{v_context}", $new_dommain_uuid, $xmlString);
                $xmlString = str_replace("{v_pin_number}", generate_password($length, 1), $xmlString);

                $xml = simplexml_load_string($xmlString);
                $json = json_encode($xml);
                $newDialplan = json_decode($json, true);

                if (!empty($newDialplan))
                {
                    if (empty($newDialplan['condition'][0]))
                    {
                        $tmp = $newDialplan['condition'];
                        unset($newDialplan['condition']);
                        $newDialplan['condition'][0] = $tmp;
                    }
                }

                $app_uuid_exists = false;
                foreach($dialplasn as $dialplan)
                {
                    if ($dialplan['@attributes']['app_uuid'] == $dialplan->app_uuid)
                    {
                        $app_uuid_exists = true;
                        break;
                    }
                }

                if (!$app_uuid_exists)
                {
                    $dialplan_global = (isset($newDialplan['@attributes']['global']) && $newDialplan['@attributes']['global'] == "true");
                    $dialplan_context = $dialplan['@attributes']['context'];
                    $dialplan_context = str_replace("\${domain_name}", $newDomain->domain_name, $dialplan_context);
                    $domain_uuid = $dialplan_global ? null : $newDomain->domain_uuid;
                    $x = 0;

                    $array['dialplans'][$x]['domain_uuid'] = $domain_uuid;
                    $array['dialplans'][$x]['app_uuid'] = $dialplan['@attributes']['app_uuid'];
                    $array['dialplans'][$x]['dialplan_name'] = $dialplan['@attributes']['name'];
                    $array['dialplans'][$x]['dialplan_number'] = $dialplan['@attributes']['number'];
                    $array['dialplans'][$x]['dialplan_context'] = $dialplan_context;
                    if (!empty($dialplan['@attributes']['destination']))
                    {
                        $array['dialplans'][$x]['dialplan_destination'] = $dialplan['@attributes']['destination'];
                    }
                    if (!empty($dialplan['@attributes']['continue']))
                    {
                        $array['dialplans'][$x]['dialplan_continue'] = $dialplan['@attributes']['continue'];
                    }
                    $array['dialplans'][$x]['dialplan_order'] = $dialplan['@attributes']['order'];
                    if (!empty($dialplan['@attributes']['enabled']))
                    {
                        $array['dialplans'][$x]['dialplan_enabled'] = $dialplan['@attributes']['enabled'];
                    }
                    else
                    {
                        $array['dialplans'][$x]['dialplan_enabled'] = "true";
                    }
                    if (!empty($dialplan['@attributes']['description']))
                    {
                        $array['dialplans'][$x]['dialplan_description'] = $dialplan['@attributes']['description'];
                    }

                    $y = 0;
                    $group = 0;
                    $order = 5;
                    $newInsertedDialplan = Dialplan::create($array['dialplans'][$x]);
                    $dialplan_uuid = $newInsertedDialplan->dialplan_uuid;
                    if (isset($dialplan['condition'])) {
                        foreach ($dialplan['condition'] as &$row)
                        {

                            $array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $domain_uuid;
                            $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan_uuid;
                            $ref_dialplan_uuid = $modelo->dialplan_uuid;
                            $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'condition';
                            $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $order;
                            $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = $row['@attributes']['field'];
                            $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = $row['@attributes']['expression'];
                            if (!empty($row['@attributes']['break']))
                            {
                                $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_break'] = $row['@attributes']['break'];
                            }
                            $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = $group;
                            if (isset($row['@attributes']['enabled']))
                            {
                                $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = $row['@attributes']['enabled'];
                            }
                            else
                            {
                                $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = 'true';
                            }
                            $y++;

                            if (!empty($row['action']) || !empty($row['anti-action']))
                            {
                                $condition_self_closing_tag = false;
                                if (empty($row['action'][0]))
                                {
                                    if ($row['action']['@attributes']['application'])
                                    {
                                        $tmp = $row['action'];
                                        unset($row['action']);
                                        $row['action'][0] = $tmp;
                                    }
                                }
                                if (empty($row['anti-action'][0]))
                                {
                                    if ($row['anti-action']['@attributes']['application'])
                                    {
                                        $tmp = $row['anti-action'];
                                        unset($row['anti-action']);
                                        $row['anti-action'][0] = $tmp;
                                    }
                                }
                                $order = $order + 5;
                                if (isset($row['action']))
                                {
                                    foreach ($row['action'] as &$row2)
                                    {
                                        $array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $domain_uuid;
                                        $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan_uuid;
                                        $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
                                        $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $order;
                                        $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = $row2['@attributes']['application'];
                                        $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = $row2['@attributes']['data'];
                                        if (!empty($row2['@attributes']['inline']))
                                        {
                                            $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_inline'] = $row2['@attributes']['inline'];
                                        }
                                        else
                                        {
                                            $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_inline'] = null;
                                        }
                                        $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = $group;
                                        if (isset($row2['@attributes']['enabled']))
                                        {
                                            $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = $row2['@attributes']['enabled'];
                                        }
                                        else
                                        {
                                            $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = 'true';
                                        }
                                        $y++;

                                        //increase the order number
                                        $order = $order + 5;
                                    }
                                }
                                if (isset($row['anti-action'])) {
                                    foreach ($row['anti-action'] as &$row2)
                                    {
                                        $array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $domain_uuid;
                                        $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan_uuid;
                                        $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'anti-action';
                                        $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $order;
                                        $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = $row2['@attributes']['application'];
                                        $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = $row2['@attributes']['data'];
                                        if (!empty($row2['@attributes']['inline']))
                                        {
                                            $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_inline'] = $row2['@attributes']['inline'];
                                        }
                                        else
                                        {
                                            $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_inline'] = null;
                                        }
                                        $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = $group;
                                        if (isset($row2['@attributes']['enabled']))
                                        {
                                            $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = $row2['@attributes']['enabled'];
                                        }
                                        else
                                        {
                                            $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = 'true';
                                        }
                                        $y++;

                                        //increase the order number
                                        $order = $order + 5;
                                    }
                                }
                            }
                            else
                            {
                                $condition_self_closing_tag = true;
                            }

                            //if not a self closing tag then increment the group
                            if (!$condition_self_closing_tag)
                            {
                                $group++;
                            }

                            //increment the values
                            $order = $order + 5;

                            //increase the row number
                            //$x++;
                        }

                        // We have $array with all data, lets start pushing into the model
                        foreach ($array['dialplans'][$x]['dialplan_details'] as $newDialplanDetail)
                        {
                            $newInsertedDialplanDetail = DialplanDetail::create($newDialplanDetail);
                        }
                        $xmlPayload = $this->dialplanRepository->buildXML($newInsertedDialplan);
                        if(App::hasDebugModeEnabled())
                        {
                            Log::notice('['.__FILE__.':'.__LINE__.']['.__CLASS__.']['.__METHOD__.'] $xmlPayload: '.$xmlPayload);
                        }
                        $newInsertedDialplan->update(['xml' => $xmlPayload]);

                    }   // app_uuid_exists
                }
            }
        }
    }
}
