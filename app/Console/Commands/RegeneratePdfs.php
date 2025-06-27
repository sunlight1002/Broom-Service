<?php

namespace App\Console\Commands;

use App\Models\Form;
use App\Services\WorkerFormService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class RegeneratePdfs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pdfs:regenerate {--form-id= : Regenerate specific form by ID} {--all : Regenerate all forms}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate corrupted PDFs for existing forms';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting PDF regeneration...');

        if ($formId = $this->option('form-id')) {
            // Regenerate specific form
            $form = Form::find($formId);
            if (!$form) {
                $this->error("Form with ID {$formId} not found.");
                return 1;
            }
            
            $this->regenerateForm($form);
        } elseif ($this->option('all')) {
            // Regenerate all forms
            $forms = Form::whereNotNull('pdf_name')->get();
            $this->info("Found {$forms->count()} forms with PDFs to regenerate.");
            
            $bar = $this->output->createProgressBar($forms->count());
            $bar->start();
            
            foreach ($forms as $form) {
                $this->regenerateForm($form, false);
                $bar->advance();
            }
            
            $bar->finish();
            $this->newLine();
        } else {
            $this->error('Please specify --form-id or --all option.');
            return 1;
        }

        $this->info('PDF regeneration completed!');
        return 0;
    }

    private function regenerateForm($form, $showOutput = true)
    {
        if ($showOutput) {
            $this->info("Regenerating PDF for form ID: {$form->id} - Type: {$form->type}");
        }

        try {
            // Generate new PDF
            $newFileName = 'regenerated-' . time() . '-' . $form->id . '.pdf';
            $success = WorkerFormService::regenerateExistingPdf($form, $newFileName);

            if ($success) {
                // Update the form with the new PDF name
                $form->update(['pdf_name' => $newFileName]);
                
                if ($showOutput) {
                    $this->info("✓ Successfully regenerated PDF: {$newFileName}");
                }
            } else {
                if ($showOutput) {
                    $this->error("✗ Failed to regenerate PDF for form ID: {$form->id}");
                }
            }
        } catch (\Exception $e) {
            if ($showOutput) {
                $this->error("✗ Error regenerating PDF for form ID {$form->id}: " . $e->getMessage());
            }
        }
    }
}
