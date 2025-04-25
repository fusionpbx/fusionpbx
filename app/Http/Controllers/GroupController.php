<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Domain;
use App\Http\Requests\GroupRequest;
use App\Repositories\GroupRepository;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    protected $groupRepository;    

    public function __construct(GroupRepository $groupRepository)
    {
        $this->groupRepository = $groupRepository;
    }


    public function index()
    {
        return view('pages.groups.index');
    }

    public function create()
    {
        $domains = Domain::all();

        return view('pages.groups.form', compact('domains'));
    }

    public function store(GroupRequest $request)
    {
        $this->groupRepository->create($request->validated());

        return redirect()->route('groups.index');
    }

    public function show(Group $group)
    {
        //
    }

    public function edit(Group $group)
    {
        $domains = Domain::all();

        return view('pages.groups.form', compact('group', 'domains'));
    }

    public function update(GroupRequest $request, Group $group)
    {
        $this->groupRepository->update($group, $request->all());

        return redirect()->route('groups.index');
    }

    public function destroy(Group $group)
    {
        try {
            $this->groupRepository->delete($group);
            return redirect()->route('groups.index');
        } catch (\Exception $e) {
            return redirect()->route('groups.index')->with('error', $e->getMessage());
        }
    }


    public function copy(Group $group)
    {
        try {
            if ($this->groupRepository->userHasPermission('group_add')) {
                $this->groupRepository->copy($group);
                return redirect()->route('groups.index')->with('success', 'Group copied successfully!');
            }
            return redirect()->route('groups.index')->with('error', 'You do not have permission to copy groups');
        } catch (\Exception $e) {
            return redirect()->route('groups.index')->with('error', 'Failed to copy group: ' . $e->getMessage());
        }
    }
}