<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\CityProgram;
use App\Models\Department;
use App\Models\InvestigationLine;
use App\Models\Professor;
use App\Models\Program;
use App\Models\Project;
use App\Models\ProjectStatus;
use App\Models\ResearchGroup;
use App\Models\ThematicArea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProjectReportControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $researchStaff;

    private User $committeeLeader;

    private CityProgram $primaryCityProgram;

    private CityProgram $secondaryCityProgram;

    private ThematicArea $thematicArea;

    protected function setUp(): void
    {
        parent::setUp();

        $department = new Department();
        $department->name = 'Antioquia';
        $department->save();

        $city = new City();
        $city->name = 'Medellin';
        $city->department_id = $department->id;
        $city->save();

        $researchGroup = new ResearchGroup();
        $researchGroup->name = 'Grupo Base';
        $researchGroup->initials = 'GB';
        $researchGroup->description = 'Grupo de prueba';
        $researchGroup->save();

        $primaryProgram = Program::create([
            'code' => 101,
            'name' => 'Ingenieria De Sistemas',
            'research_group_id' => $researchGroup->id,
        ]);

        $secondaryProgram = Program::create([
            'code' => 202,
            'name' => 'Ingenieria Industrial',
            'research_group_id' => $researchGroup->id,
        ]);

        $this->primaryCityProgram = CityProgram::create([
            'city_id' => $city->id,
            'program_id' => $primaryProgram->id,
        ]);

        $this->secondaryCityProgram = CityProgram::create([
            'city_id' => $city->id,
            'program_id' => $secondaryProgram->id,
        ]);

        $line = new InvestigationLine();
        $line->name = 'Transformacion Digital';
        $line->description = 'Linea de prueba';
        $line->research_group_id = $researchGroup->id;
        $line->save();

        $this->thematicArea = new ThematicArea();
        $this->thematicArea->name = 'Analitica';
        $this->thematicArea->description = 'Area de prueba';
        $this->thematicArea->investigation_line_id = $line->id;
        $this->thematicArea->save();

        $this->researchStaff = $this->createUser('staff@example.com', 'research_staff');
        $this->committeeLeader = $this->createUser('leader@example.com', 'committee_leader');

        $this->createProfessorProfile($this->committeeLeader, $this->primaryCityProgram, true, 'LDR001');

        $primaryProfessorUser = $this->createUser('primary.prof@example.com', 'professor');
        $secondaryProfessorUser = $this->createUser('secondary.prof@example.com', 'professor');

        $primaryProfessor = $this->createProfessorProfile($primaryProfessorUser, $this->primaryCityProgram, false, 'PROF001');
        $secondaryProfessor = $this->createProfessorProfile($secondaryProfessorUser, $this->secondaryCityProgram, false, 'PROF002');

        $pending = $this->createStatus('Pendiente');
        $approved = $this->createStatus('Aprobado');

        $projectInPrimaryProgram = Project::create([
            'title' => 'Proyecto Uno',
            'evaluation_criteria' => 'Criterio base',
            'thematic_area_id' => $this->thematicArea->id,
            'project_status_id' => $pending->id,
        ]);
        $projectInPrimaryProgram->professors()->attach($primaryProfessor->id);

        $projectInSecondaryProgram = Project::create([
            'title' => 'Proyecto Dos',
            'evaluation_criteria' => 'Criterio base',
            'thematic_area_id' => $this->thematicArea->id,
            'project_status_id' => $approved->id,
        ]);
        $projectInSecondaryProgram->professors()->attach($secondaryProfessor->id);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('reports.module-overview'))
            ->assertRedirect(route('login'));
    }

    public function test_research_staff_can_view_the_report_test_screen(): void
    {
        $response = $this->actingAs($this->researchStaff)->get(route('reports.module-overview'));

        $response->assertOk();
        $response->assertViewIs('reports.module-overview');
        $response->assertSee('Modulo de Reportes');
    }

    public function test_committee_leader_only_sees_projects_from_their_program(): void
    {
        $response = $this->actingAs($this->committeeLeader)->get(route('reports.module-overview'));

        $response->assertOk();

        $reportData = $response->viewData('reportData');

        $this->assertSame(1, $reportData['total']);
        $this->assertSame(['Pendiente'], $reportData['categories']);
        $this->assertSame([1], $reportData['values']);
    }

    public function test_same_route_can_export_csv(): void
    {
        $response = $this->actingAs($this->researchStaff)
            ->get(route('reports.module-overview', ['export' => 'csv']));

        $response->assertOk();
        $this->assertSame('text/csv; charset=UTF-8', $response->headers->get('content-type'));
        $this->assertStringContainsString('Categoria,Valor,Porcentaje', $response->streamedContent());
    }

    private function createUser(string $email, string $role): User
    {
        return User::create([
            'name' => 'Usuario ' . $role,
            'email' => $email,
            'password' => Hash::make('password'),
            'role' => $role,
            'state' => true,
        ]);
    }

    private function createProfessorProfile(User $user, CityProgram $cityProgram, bool $isCommitteeLeader, string $cardId): Professor
    {
        $professor = new Professor();
        $professor->card_id = $cardId;
        $professor->name = 'Profesor';
        $professor->last_name = $isCommitteeLeader ? 'Lider' : 'Base';
        $professor->phone = '3000000000';
        $professor->city_program_id = $cityProgram->id;
        $professor->user_id = $user->id;
        $professor->committee_leader = $isCommitteeLeader;
        $professor->save();

        return $professor;
    }

    private function createStatus(string $name): ProjectStatus
    {
        $status = new ProjectStatus();
        $status->name = $name;
        $status->description = 'Descripcion de prueba';
        $status->save();

        return $status;
    }
}
