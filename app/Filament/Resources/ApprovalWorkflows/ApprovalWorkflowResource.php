<?php

namespace App\Filament\Resources\ApprovalWorkflows;

use App\Filament\Resources\ApprovalWorkflows\Pages\CreateApprovalWorkflow;
use App\Filament\Resources\ApprovalWorkflows\Pages\EditApprovalWorkflow;
use App\Filament\Resources\ApprovalWorkflows\Pages\ListApprovalWorkflows;
use App\Filament\Resources\ApprovalWorkflows\Schemas\ApprovalWorkflowForm;
use App\Filament\Resources\ApprovalWorkflows\Tables\ApprovalWorkflowsTable;
use App\Models\ApprovalWorkflow;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ApprovalWorkflowResource extends Resource
{
    protected static ?string $model = ApprovalWorkflow::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Surat Disposisi';

    protected static ?string $modelLabel = 'Surat Disposisi';

    protected static ?string $recordTitleAttribute = 'urutan';

    public static function form(Schema $schema): Schema
    {
        return ApprovalWorkflowForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ApprovalWorkflowsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * Resource teknis untuk data mentah tabel approval_workflows. Hanya PIC
     * yang perlu (dan boleh) melihat/mengubah ini secara manual.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->role?->slug === 'pic';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListApprovalWorkflows::route('/'),
            'create' => CreateApprovalWorkflow::route('/create'),
            'edit' => EditApprovalWorkflow::route('/{record}/edit'),
        ];
    }
}
