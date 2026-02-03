<?php
// routes/backend.php

use App\Http\Controllers\Backend\AccountsNoteApplicationController;
use App\Models\Upload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\Backend\RoleController;
use App\Http\Controllers\Backend\UserController;
use App\Http\Controllers\Backend\EventController;
use App\Http\Controllers\Backend\NotesController;
use App\Http\Controllers\Backend\OfferController;
use App\Http\Controllers\Backend\StaffController;
use App\Http\Controllers\Backend\BranchController;
use App\Http\Controllers\Backend\InvoiceController;
use App\Http\Controllers\Backend\JobTypeController;
use App\Http\Controllers\Backend\TenancyController;
use App\Http\Controllers\Backend\WebsiteController;
use App\Http\Controllers\Backend\NoteTypeController;
use App\Http\Controllers\Backend\PropertyController;
use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\Backend\DocumentsController;
use App\Http\Controllers\Backend\EventTypeController;
use App\Http\Controllers\Backend\WorkOrderController;
use App\Http\Controllers\Backend\BankDetailController;
use App\Http\Controllers\Backend\ComplianceController;
use App\Http\Controllers\Backend\OwnerGroupController;
use App\Http\Controllers\Backend\DesignationController;
use App\Http\Controllers\Backend\TenancyTypeController;
use App\Http\Controllers\Backend\TransactionController;
use App\Http\Controllers\Backend\AuthenticateController;
use App\Http\Controllers\Backend\DocumentTypeController;
use App\Http\Controllers\Backend\EstateChargeController;
use App\Http\Controllers\Backend\EventSubTypeController;
use App\Http\Controllers\Backend\UserCategoryController;
use App\Http\Controllers\Backend\AccountHeaderController;
use App\Http\Controllers\Backend\EmailTemplateController;
use App\Http\Controllers\Backend\PropertyRepairController;
use App\Http\Controllers\Backend\PurchaseInvoiceController;
use App\Http\Controllers\Backend\BusinessSettingsController;
use App\Http\Controllers\Backend\EstateChargeItemController;
use App\Http\Controllers\Backend\TenancySubStatusController;
use App\Http\Controllers\Backend\TransactionCategoryController;


// Login Routes
Route::get('/login', [AuthenticateController::class, 'index'])->name('backend.login');
Route::post('/login', [AuthenticateController::class, 'login'])->name('backend.login.post');
Route::get('/logout', [AuthenticateController::class, 'logout'])->name('backend.logout');

// Redirect / to login page if not authenticated
Route::get('/', function () {
    return redirect()->route('backend.login');
});

Route::middleware('auth')->group(function () {
    Route::get('/clear-cache', function () {
        // Clear application cache
        Artisan::call('cache:clear');

        // Clear configuration cache
        Artisan::call('config:clear');

        // Optionally clear other caches you might need
        Artisan::call('route:clear');
        Artisan::call('view:clear');

        // Flash a success message
        flash('Cache cleared successfully')->success();

        // Redirect back to the previous page
        return back();
    })->name('cache.clear');

    Route::get('/search-properties', function (Request $request) {
        return searchProperties($request);
    })->name('properties.search');

    Route::get('properties/search-ajax', [PropertyController::class, 'searchAjax'])->name('backend.properties.search-ajax');

    // Route::get('/get_users_info_by_property/{propertyId}/users/{categoryId}', function ($propertyId, $categoryId) {
    //     return response()->json(get_users_by_property_and_category($propertyId, $categoryId));
    // })->name('admin.getUsersByProperty');
    Route::get('/get_users_info_by_property/{propertyId}/users/{roleId}', function ($propertyId, $roleId) {
        return response()->json(get_users_by_property_and_role($propertyId, $roleId));
    })->name('admin.getUsersByProperty');


    Route::get('/get_tenants_by_property/{propertyId}', function ($propertyId) {
        return response()->json(get_tenants_by_property($propertyId));
    })->name('admin.getTenantsByProperty');

    // Dashboard Route
    Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('backend.dashboard');
    // Route::get('/dashboard', [DashboardController::class, 'dashboard'])->middleware('can:view-dashboard')->name('backend.dashboard');

    Route::resource('user-categories', UserCategoryController::class);

    Route::name('admin.')->group(function () {
        // Property
        Route::prefix('properties')->name('properties.')->controller(PropertyController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/store', 'store')->name('store');
            Route::get('/view/{id}', 'view')->name('view');
            Route::get('/edit/{id}', 'edit')->name('edit');
            Route::post('/update/{id}', 'update')->name('update');
            Route::post('/delete/{id}', 'destroy')->name('delete');
            Route::get('/step/{step}', 'getStepView')->name('step');
            Route::get('/quick-create', 'quick')->name('quick');
            Route::get('/quick_step/{step}', 'getQuickStepView')->name('quick_step');
            Route::post('/quick-store', 'quickStore')->name('quick_store');
            Route::get('/deleted', 'showSoftDeletedProperties')->name('soft_deleted');
            Route::post('/restore/{id}', 'restore')->name('restore');
            Route::post('/bulk-restore', 'bulkRestore')->name('bulk-restore');
            // Route::get('/{property_id}/{tabname}',  'showTabContent')->name('tabcontent');

            Route::get('/load-form', 'loadForm')->name('loadForm');
            Route::post('/save-form', 'saveForm')->name('saveForm');
            
            Route::get('/ajax', 'ajaxList')->name('ajax');
        });

        // Designation
        Route::prefix('designations')->name('designations.')->controller(DesignationController::class)->group(function () {
            Route::get('/', 'index')->name('index');  // List all designations
            Route::get('/create', 'create')->name('create');  // Show create form
            Route::post('/store', 'store')->name('store');  // Store new designation
            Route::get('/edit/{designation}', 'edit')->name('edit');  // Show edit form
            Route::put('/update/{designation}', 'update')->name('update');  // Update designation
            Route::delete('/delete/{designation}', 'destroy')->name('destroy');  // Delete designation
        });

        // Branch
        Route::prefix('branches')->name('branches.')->controller(BranchController::class)->group(function () {
            Route::get('/', 'index')->name('index');  // List all branches
            Route::get('/create', 'create')->name('create');  // Show create form
            Route::post('/store', 'store')->name('store');  // Store new branch
            Route::get('/edit/{branch}', 'edit')->name('edit');  // Show edit form
            Route::put('/update/{branch}', 'update')->name('update');  // Update branch
            Route::delete('/delete/{branch}', 'destroy')->name('destroy');  // Delete branch
        });

        // Note Types
        Route::prefix('note-types')->name('note-types.')->controller(NoteTypeController::class)->group(function () {
            Route::get('/all', 'index')->name('index');              // List all note types
            Route::get('/show', 'show')->name('show');              // List all note types
            Route::get('/create', 'create')->name('create');      // Show create form
            Route::post('/store', 'store')->name('store');        // Store new note type
            Route::get('/edit/{id}', 'edit')->name('edit'); // Show edit form
            Route::put('/update/{id}', 'update')->name('update'); // Update note type
            Route::delete('/delete/{id}', 'destroy')->name('destroy'); // Delete note type
        });

        // Document Types
        Route::prefix('document-types')->name('document-types.')->controller(DocumentTypeController::class)->group(function () {
            Route::get('/all', 'index')->name('index');              // List all note types
            Route::get('/show', 'show')->name('show');              // List all note types
            Route::get('/create', 'create')->name('create');      // Show create form
            Route::post('/store', 'store')->name('store');        // Store new note type
            Route::get('/edit/{id}', 'edit')->name('edit'); // Show edit form
            Route::put('/update/{id}', 'update')->name('update'); // Update note type
            Route::delete('/delete/{id}', 'destroy')->name('destroy'); // Delete note type
        });

        // Users
        Route::prefix('users')->name('users.')->controller(UserController::class)->group(function () {
            Route::get('/', 'index')->name('index');  // List all users
            Route::get('/create', 'create')->name('create');  // Show create form
            Route::get('/user_step/{step}', 'getQuickStepView')->name('user_step');  // Get quick step view
            Route::post('/store', 'userStore')->name('store');  // Store user
            Route::post('/quick-store-user', 'quicklyStoreUser')->name('quick_user_store');  // Quick store user
            Route::get('/properties/search', 'searchProperties')->name('properties.search');  // Search properties
            Route::get('/show/{id}', 'show')->name('show');  // Show individual user
            Route::get('/edit/{id}', 'edit')->name('edit');  // Show edit form
            Route::post('/update/{id}', 'update')->name('update');  // Update user
            Route::post('/delete/{id}', 'delete')->name('delete');  // Delete user
            
            Route::get('/load-form', 'loadForm')->name('loadForm');
            Route::post('/save-form', 'saveForm')->name('saveForm');

            Route::get('/ajax', 'ajaxList')->name('ajax');  // AJAX endpoint to list users for a select dropdown
            Route::get('/profile', 'profile')->name('profile.show');  // Show user profile
            Route::get('/profile/edit', 'profileEdit')->name('profile.edit');  // Show user profile edit form
            Route::post('/profile/update', 'profileUpdate')->name('profile.update');  // Update user profile
            Route::post('/profile/password', 'profilePasswordUpdate')->name('profile.password');  // Update user password
        });

        // Estate Charges
        Route::prefix('estate-charges')->name('estate-charges.')->controller(EstateChargeController::class)->group(function () {
            Route::get('/', 'index')->name('index');  // List all estate charges
            Route::get('/create', 'create')->name('create');  // Show create form
            Route::post('/store', 'store')->name('store');  // Store new estate charge
            Route::get('/{estateCharge}/show', 'show')->name('show');  // Show individual estate charge
            Route::get('/{estateCharge}/edit', 'edit')->name('edit');  // Show edit form
            Route::put('/{estateCharge}/update', 'update')->name('update');  // Update estate charge
            Route::delete('/{estateCharge}/destroy', 'destroy')->name('destroy');  // Delete estate charge
        });

        // Estate Charge Items
        Route::prefix('estate-charges-items')->name('estate-charges-items.')->controller(EstateChargeItemController::class)->group(function () {
            Route::get('/', 'index')->name('index');  // List all estate charge items
            Route::get('/create', 'create')->name('create');  // Show create form
            Route::post('/store', 'store')->name('store');  // Store new estate charge item
            Route::get('/{estateChargeItem}', 'show')->name('show');  // Show individual estate charge item
            Route::get('/{estateChargeItem}/edit', 'edit')->name('edit');  // Show edit form
            Route::put('/{estateChargeItem}/update', 'update')->name('update');  // Update estate charge item
            Route::delete('/{estateChargeItem}/destroy', 'destroy')->name('destroy');  // Delete estate charge item
        });

        // Owner Groups
        Route::prefix('owner-groups')->name('owner-groups.')->controller(OwnerGroupController::class)->group(function () {
            Route::get('/', 'index')->name('index');  // List all owner groups
            Route::get('/create', 'create')->name('create');  // Show create form
            Route::get('/create-group', 'createGroup')->name('create_group');  // Show create group form
            Route::post('/store', 'store')->name('store');  // Store new owner group
            Route::post('/store-group', 'storeGroup')->name('store_group');  // Store new owner group subgroup
            Route::post('/update-group/{id}', 'updateGroup')->name('update_group');  // Update subgroup
            Route::post('/delete-group/{id}', 'deleteGroup')->name('delete_group');  // Delete subgroup
            Route::get('/{ownerGroup}/show', 'show')->name('show');  // Show individual owner group
            Route::get('/{ownerGroup}/edit', 'edit')->name('edit');  // Show edit form
            Route::put('/{ownerGroup}/update', 'update')->name('update');  // Update owner group
            Route::post('/{ownerGroup}/delete', 'destroy')->name('destroy');  // Delete owner group
            Route::post('/update-main/{id}', 'updateMain')->name('updateMain');  // Update main owner group
        });

        // Tenancy
        Route::prefix('tenancies')->name('tenancies.')->controller(TenancyController::class)->group(function () {
            Route::get('/create', 'create')->name('create');  // Show create form
            Route::post('/store', 'store')->name('store');  // Store new tenancy
            Route::get('/{id}', 'show')->name('show');  // Show individual tenancy
            Route::get('/{id}/edit', 'edit')->name('edit');  // Show edit form
            Route::post('/{id}/update', 'update')->name('update');  // Update tenancy
            Route::post('/{id}/delete', 'destroy')->name('delete');  // Delete tenancy
        });

        // Keeping this route separate since it follows a different URL structure
        Route::get('/properties/{propertyId}/tenancies', [TenancyController::class, 'index'])->name('tenancies.index');

        // Offer
        Route::prefix('offers')->name('offers.')->controller(OfferController::class)->group(function () {
            Route::get('/', 'index')->name('index');  // List all offers
            Route::get('/create', 'create')->name('create');  // Show create form
            Route::post('/store', 'store')->name('store');  // Store new offer
            Route::get('/{offer}/edit', 'edit')->name('edit');  // Show edit form
            Route::put('/{offer}/update', 'update')->name('update');  // Update offer
            Route::delete('/{offer}/delete', 'destroy')->name('destroy');  // Delete offer
        });

        // don't change url its used in property-offer.js file
        Route::post('offers/{id}/set-main-person', [OfferController::class, 'setMainPerson'])->name('offers.setMainPerson');
        Route::post('offers/{id}/update-status', [OfferController::class, 'updateStatus'])->name('offers.updateStatus');

        Route::prefix('tenancy-sub-statuses')->name('tenancy_sub_statuses.')->controller(TenancySubStatusController::class)->group(function () {
            Route::get('/', 'index')->name('index');  // List all tenancy sub statuses
            Route::get('/show', 'show')->name('show');  // Show individual sub status
            Route::get('/create', 'create')->name('create');  // Show create form
            Route::post('/store', 'store')->name('store');  // Store new tenancy sub status
            Route::get('/edit/{tenancySubStatus}', 'edit')->name('edit');  // Show edit form
            Route::put('/update/{tenancySubStatus}', 'update')->name('update');  // Update tenancy sub status
            Route::delete('/delete/{tenancySubStatus}', 'destroy')->name('destroy');  // Delete tenancy sub status
        });

        Route::prefix('tenancy-types')->name('tenancy_types.')->controller(TenancyTypeController::class)->group(function () {
            Route::get('/', 'index')->name('index');  // List all tenancy types
            Route::get('/show', 'show')->name('show');  // Show individual tenancy type
            Route::get('/create', 'create')->name('create');  // Show create form
            Route::post('/store', 'store')->name('store');  // Store new tenancy type
            Route::get('/edit/{tenancyType}', 'edit')->name('edit');  // Show edit form
            Route::put('/update/{tenancyType}', 'update')->name('update');  // Update tenancy type
            Route::delete('/delete/{tenancyType}', 'destroy')->name('destroy');  // Delete tenancy type
        });

        Route::prefix('compliance')->name('compliance.')->controller(ComplianceController::class)->group(function () {
            Route::get('/type/form/{complianceTypeId}/{complianceRecordId?}', 'getComplianceForm')->name('type.form');
            Route::post('/store', 'storeCompliance')->name('store');
            Route::post('/update', 'updateCompliance')->name('update');
            Route::delete('/delete/{complianceRecordId}', 'deleteCompliance')->name('delete');
        });

        Route::prefix('bank_details')->name('bank_details.')->controller(BankDetailController::class)->group(function () {
            Route::get('/show/{id}', 'show')->name('show');
            Route::post('/store', 'store')->name('store');
            Route::post('/delete/{id}', 'destroy')->name('delete');
        });

        Route::prefix('notes')->name('notes.')->controller(NotesController::class)->group(function () {
            // List notes (with optional filtering for noteable_type, noteable_id, note_id)
            Route::get('/list', 'listNotes')->name('list');
            Route::get('/create', 'create')->name('create');
            Route::get('/{note}/edit', 'edit')->name('edit');
            // Show single note by ID
            Route::get('/show/{id}', 'showNote')->name('show');
            // Create or update a note (store or update)
            Route::post('/save', 'storeOrUpdate')->name('save');
            // Delete a note by ID
            Route::post('/delete/{id}', 'deleteNote')->name('delete');
        });

        Route::prefix('documents')->name('documents.')->controller(DocumentsController::class)->group(function () {
            // List documents (with optional filtering for documentable_type, documentable_id, upload_id)
            Route::get('/list', 'listDocuments')->name('list');
            Route::get('/create', 'create')->name('create');
            Route::get('/{document}/edit', 'edit')->name('edit');
            // Show single document by ID
            Route::get('/show/{id}', 'showDocument')->name('show');
            // Create or update a document (store or update)
            Route::post('/save', 'storeOrUpdate')->name('save');
            // Delete a document by ID
            Route::post('/delete/{id}', 'deleteDocument')->name('delete');
        });

        Route::prefix('/property-repairs')->group(function () {
            Route::controller(PropertyRepairController::class)->group(function () {
                Route::get('/issue-list', 'index')->name('property_repairs.index');
                Route::get('repair-show/{id}', 'show')->name('property_repairs.show');
                Route::get('repair-edit/{id}/edit', 'edit')->name('property_repairs.edit');
                Route::put('repair-update/{id}', 'update')->name('property_repairs.update');
                Route::delete('repair-delete/{id}', 'destroy')->name('property_repairs.delete');

                Route::get('/raise-repair-issue-create', 'repairRaise')->name('property_repairs.create');  // List all property repairs
                Route::get('/repair-category/{categoryId}/subcategories', 'getSubCategories')->name('property_repairs.getSubCategories');
                Route::post('/raise-issue-store', 'raiseIssueStore')->name(name: 'property_repairs.store');  // List all property repairs
                Route::post('/repair/check-last-step', 'checkLastStep')->name('repair.checkLastStep');
                Route::get('/get-repair-categories', 'getCategories')->name('get.repair.categories');
                Route::get('/selected-property/tenants', 'getPropertyTenants')->name('get.property_repairs.tenants');
                Route::get('/repair/{repair}/workorder-invoice', 'workOrderInvoice')->name('repair.workorder.invoice');

                Route::get('/load-form', 'loadForm')->name('property_repairs.loadForm');
                Route::post('/save-form', 'saveForm')->name('property_repairs.saveForm');

                Route::get('/ajax', 'ajaxList')->name( 'property_repairs.ajax');  // AJAX endpoint to list property repairs for a select dropdown
            });
        });

        Route::prefix('/job-types')->group(function () {
            Route::controller(JobTypeController::class)->group(function () {
                Route::get('/list', 'index')->name('job_types.index');  // List all job types
                Route::get('/show/{id}', 'show')->name('job_types.show');  // Show single job type
                Route::get('/edit/{id}', 'edit')->name('job_types.edit');  // Edit job type
                Route::put('/update/{id}', 'update')->name('job_types.update');  // Update job type
                Route::delete('/delete/{id}', 'destroy')->name('job_types.delete');  // Delete job type

                Route::get('/create', 'create')->name('job_types.create');  // Show form to create job type
                Route::post('/store', 'store')->name('job_types.store');  // Store new job type

                Route::get('/parent/{parentId}/subcategories', 'getSubTypes')->name('job_types.getSubCategories');  // Fetch subcategories
                Route::get('/get-all', 'getAllJobTypes')->name('job_types.getAll');  // Fetch all job types
            });
        });

        Route::prefix('/work-orders')->group(function () {
            Route::controller(WorkOrderController::class)->group(function () {
                Route::post('/store', 'store')->name('work_orders.store');  // Save new work order
                Route::get('/get/{repairIssueId}', 'getWorkOrder')->name('work_orders.get');  // Get work order by repair issue id
                Route::get('/generate-pdf/{id}', 'generateWorkOrderPDF')->name('workorder.generate.invoice');
            });
        });

        Route::prefix('/invoices')->group(function () {
            Route::controller(InvoiceController::class)->group(function () {
                Route::get('/', 'index')->name('invoices.index');
                Route::post('/generate/{workOrderId}', 'createFromWorkOrder')->name('invoices.generate');
                Route::get('/view/{id}', 'show')->name('invoices.show');
                Route::get('/download/{id}', 'download')->name('invoices.download');
                Route::post('/mark-paid/{id}', 'markAsPaid')->name('invoices.mark_paid');

                Route::get('/edit/{invoice}', 'edit')->name('invoices.edit');
                Route::put('/update/{invoice}', 'update')->name('invoices.update');

                Route::get('/search', 'ajaxSearch')->name('invoices.search');
                Route::get('/{invoice}/json', 'ajaxGet')->name('invoices.json');
            });
        });
    });

    /*Route::group(['prefix' => 'calendar', 'as' => 'backend.events.'], function () {
        Route::controller(EventController::class)->group(function () {
            Route::get('/events', 'index')->name('index');
            Route::get('/events/create', 'create')->name('create');
            Route::post('/events/store', 'store')->name('store');
            Route::get('/events/{event}', 'show')->name('show');
            Route::get('/events/edit/{event}', 'edit')->name('edit');
            Route::get('/api/subtypes/{typeId}', 'subtypes')->name('subtypes');
            Route::put('/events/update/{event}', 'update')->name('update');
            Route::delete('/events/delete/{event}', 'destroy')->name('destroy');
        });
    });*/

    Route::group(['prefix' => 'calendar', 'as' => 'backend.events.'], function () {
        Route::controller(EventController::class)->group(function () {
            
            // Fetch all instances in a given date range for FullCalendar.
            Route::get('/', 'view')->name('calendar');
            Route::get('/instances', 'index')->name('index');

            // Create new event or master event.
            Route::post('/instances/store', 'store')->name('store');

            // “updateInstance” for instance drag/drop.
            Route::post('/instances/update/{instance}', 'updateInstance')->name('updateInstance');

            // Endpoints for master-level edits (e.g. change recurrence rule):
            Route::put('/master/update/{event}', 'updateMaster')->name('updateMaster');

            // Cancel an instance by ID.
            Route::post('/instances/cancel/{id}', 'cancelInstance')->name('cancelInstance');

            // Delete an instance by ID[single, series, future].
            Route::post('/instances/delete/{id}', 'deleteInstance')->name('deleteInstance');

            Route::post('/instances/change-status/{id}', 'changeStatus')->name('changeStatus');

        });
    });

    // Event Types CRUD
    Route::group(['prefix'=>'', 'as'=>'backend.'], function() {
        Route::resource('event-types', EventTypeController::class)
            ->names([
                'index'   => 'event_types.index',
                'create'  => 'event_types.create',
                'store'   => 'event_types.store',
                'show'    => 'event_types.show',
                'edit'    => 'event_types.edit',
                'update'  => 'event_types.update',
                'destroy' => 'event_types.destroy'
            ]);

        Route::resource('event-sub-types', EventSubTypeController::class)
            ->names([
                'index'   => 'event_sub_types.index',
                'create'  => 'event_sub_types.create',
                'store'   => 'event_sub_types.store',
                'show'    => 'event_sub_types.show',
                'edit'    => 'event_sub_types.edit',
                'update'  => 'event_sub_types.update',
                'destroy' => 'event_sub_types.destroy'
            ]);

        // AJAX route to fetch subtypes by type ID:
        Route::get('api/event-sub-types/{typeId}', [EventSubTypeController::class, 'byType'])
            ->name('api.event_sub_types.byType');

        // Account Headers CRUD
        Route::resource('account-headers', AccountHeaderController::class)
            ->names([
                'index'   => 'account_headers.index',
                'create'  => 'account_headers.create',
                'store'   => 'account_headers.store',
                'show'    => 'account_headers.show',
                'edit'    => 'account_headers.edit',
                'update'  => 'account_headers.update',
                'destroy' => 'account_headers.destroy',
            ]);
        
        
        // Transactions CRUD
        Route::resource('transactions', TransactionController::class)
            ->names([
                'index'   => 'transactions.index',
                'create'  => 'transactions.create',
                'store'   => 'transactions.store',
                'show'    => 'transactions.show',
                'edit'    => 'transactions.edit',
                'update'  => 'transactions.update',
                'destroy' => 'transactions.destroy',
            ]);
    
        // Transaction Categories CRUD
        Route::resource('transaction-categories', TransactionCategoryController::class)
            ->names([
                'index'   => 'transaction_categories.index',
                'create'  => 'transaction_categories.create',
                'store'   => 'transaction_categories.store',
                'show'    => 'transaction_categories.show',
                'edit'    => 'transaction_categories.edit',
                'update'  => 'transaction_categories.update',
                'destroy' => 'transaction_categories.destroy',
            ]);

        // Purchase invoices
        Route::resource('purchase_invoices', PurchaseInvoiceController::class)->only(['index','create','store','show','edit','update']);

        // Notes (credit/debit)
        Route::controller(AccountsNoteApplicationController::class)->group(function () {
            Route::get('credit-notes/create','createCredit')->name('credit_notes.create');
            Route::post('credit-notes','storeCredit')->name('credit_notes.store');
            Route::get('credit-notes/{creditNote}','showCredit')->name('credit_notes.show');

            Route::get('debit-notes/create','createDebit')->name('debit_notes.create');
            Route::post('debit-notes','storeDebit')->name('debit_notes.store');
            Route::get('debit-notes/{debitNote}','showDebit')->name('debit_notes.show');
                
            // Apply note
            Route::post('note-applications',[AccountsNoteApplicationController::class,'store'])->name('note_applications.store');
        });

        // Refunds route you already added earlier
        Route::post('notes/refund',[AccountsNoteApplicationController::class,'store'])->name('notes.refund.store');

    });


    // website setting
    Route::group(['prefix' => 'website', 'as' => 'website.'], function () {
        Route::controller(WebsiteController::class)->group(function () {
            Route::get('/footer', 'footer')->name('footer');
            Route::get('/header', 'header')->name('header');
            Route::get('/appearance', 'appearance')->name('appearance');
        });
    });

    // Business Settings
    Route::controller(BusinessSettingsController::class)->group(function () {
        Route::post('/business-settings/update', 'update')->name('business_settings.update');
        Route::get('/smtp-settings', 'smtp_settings')->name('smtp_settings.index');
        Route::post('/env_key_update', 'env_key_update')->name('env_key_update.update');
    });

    
    // Staff Roles
    Route::resource('roles', RoleController::class);
    Route::controller(RoleController::class)->group(function () {
        Route::get('/roles/edit/{id}', 'edit')->name('roles.edit');
        Route::get('/roles/destroy/{id}', 'destroy')->name('roles.destroy');

        // Add Permissiom
        Route::post('/roles/add_permission', 'add_permission')->name('roles.permission');
    });

    // Staff
    Route::resource('staffs', StaffController::class);
    Route::get('/staffs/destroy/{id}', [StaffController::class, 'destroy'])->name('staffs.destroy');

    
    Route::post('/upload-note-image', function (Request $request) {
        if ($request->hasFile('file')) {
            $upload = Upload::storeFile($request->file('file'));
            return response()->json(['url' => asset('storage/' . $upload->file_name)]);
        }
        return response()->json(['error' => 'No file uploaded'], 400);
    })->name('notes.upload_image');
    
    // Email Template
    Route::resource('email-templates', EmailTemplateController::class);
    Route::controller(EmailTemplateController::class)->group(function () {
        Route::get('/email-template/{id}', 'index')->name('email-templates.index');
        Route::post('/email-template/update-status', 'updateStatus')->name('email-template.update-status');
        Route::post('/test/smtp', 'testEmail')->name('test.smtp');
    });

});
