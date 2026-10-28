<?php

namespace App\Http\Controllers\Admin;

use App\Admin\Entities;
use App\Admin\FormParser;
use App\Admin\FormState;
use App\Admin\Saver;
use App\Admin\Values;
use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use App\Models\Project;
use App\Support\Labels;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(Request $request): View
    {
        $archived = $request->query('view') === 'archived';
        $projects = Project::query()->where('status', $archived ? '=' : '!=', 'archived')->orderBy('sort_order')->orderBy('created_at')->get();
        $photos = MediaAsset::query()->whereNotNull('project_id')->where('kind', 'image')->selectRaw('project_id, count(*) as n')->groupBy('project_id')->pluck('n', 'project_id');

        return view('admin.projects.index', ['projects' => $projects, 'photos' => $photos, 'archived' => $archived]);
    }

    public function create(Request $request): View
    {
        return view('admin.projects.edit', ['project' => null, 'form' => new FormState(Values::project(null), $request->session()->get('errors'))]);
    }

    public function edit(Request $request, string $id): View
    {
        $project = Entities::find('projects', $id);
        $project->photos = $project->media()->where('kind', 'image')->get();
        $project->cover = $project->photos->firstWhere('is_cover', true) ?? $project->photos->first();

        return view('admin.projects.edit', ['project' => $project, 'form' => new FormState(Values::project($project), $request->session()->get('errors'))]);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->save($request, null);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        return $this->save($request, Entities::find('projects', $id));
    }

    private function save(Request $request, ?Project $record): RedirectResponse
    {
        $p = new FormParser($request);
        $title = $p->text('title', 'Title', 140, required: true, min: 2);
        $size = $p->number('system_size_kwp', 'System size', 100000, positive: true);
        $sizeConfirmed = $p->bool('system_size_confirmed');
        if ($sizeConfirmed && $size === null && ! isset($p->errors['system_size_kwp'])) {
            $p->errors['system_size_kwp'] = 'Enter the confirmed system size, or untick “confirmed”.';
        }
        $row = [
            'title' => $title,
            'slug' => $p->slug('slug', $title),
            'location' => $p->text('location', 'Location', 120),
            'project_type' => $p->choice('project_type', 'Project type', array_keys(Labels::PROJECT_TYPE), required: true),
            'system_size_kwp' => $size,
            'system_size_confirmed' => $sizeConfirmed,
            'scope' => $p->text('scope', 'Scope', 600),
            'summary' => $p->body('summary', 'Summary', 300),
            'description' => $p->body('description', 'Description', 6000),
            'admin_notes' => $p->text('admin_notes', 'Internal notes', 2000),
            'featured' => $p->bool('featured'),
            'is_sample' => $p->bool('is_sample'),
        ];
        if (! $p->ok()) {
            return Saver::invalid($p);
        }

        return Saver::save($request, Project::class, 'projects', $record, $row);
    }
}
