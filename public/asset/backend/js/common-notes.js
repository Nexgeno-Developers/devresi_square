(function($){
  const api = '/admin/notes';

  // Load note list
  function load($c, extraParams = {}) {
    const type = $c.data('noteable-type');
    const id   = $c.data('noteable-id');
    const $list = $c.find('.notes-list');

    $list.html('<p>Loading…</p>');
    $.get(`${api}/list`, {
      noteable_type: type,
      noteable_id: id,
      ...extraParams
    }, r => {
      $list.html(r.html);
    });
  }

  // Open add/edit note form
  function loadForm(url, title) {
    $.get(url, function(html){
      $('#notesModal .modal-title').text(title);
      $('#notesModal .modal-body').html(html);
      // re-init your rich-text editor if needed
      if (typeof AIZ !== 'undefined' && AIZ.plugins && AIZ.plugins.textEditor) {
        AIZ.plugins.textEditor();
      }
      $('#notesModal').modal('show');
    }).fail(function(){
      alert('Failed to load form.');
    });
  }

  // Initialize components
  $(function(){
    $('.notes-component').each(function(){
      load($(this));
    });
  });

  // Handle filter form submit
  $(document).on('submit', '.notes-filter-form', function(e) {
    e.preventDefault();
    const $form = $(this);
    const $c = $form.closest('.notes-component');
    const formData = Object.fromEntries(new URLSearchParams($form.serialize()));
    load($c, formData);
  });

  // Handle reset button
  $(document).on('click', '.notes-reset', function () {
    const $btn = $(this);
    const $c = $btn.closest('.notes-component');
    const $filt = $c.find('.notes-filter-form');
    $filt[0].reset();
    load($c);
  });

  // Add button
  $(document).on('click', '.notes-add', function(){
    const $comp = $(this).closest('.notes-component');
    const type = encodeURIComponent($comp.data('noteable-type'));
    const id   = $comp.data('noteable-id');
    const url  = `${api}/create?noteable_type=${type}&noteable_id=${id}`;
    loadForm(url, 'Add Note');
  });

  // Edit button
  $(document).on('click', '.notes-edit', function(){
    const noteId = $(this).data('id');
    const url    = `${api}/${noteId}/edit`;
    loadForm(url, 'Edit Note');
  });

  // View button
  $(document).on('click', '.notes-view', function(){
    const noteId = $(this).data('id');
    const url    = `${api}/show/${noteId}`;
    $.get(url, function(res){
      $('#notesModal .modal-title').text('View Note');
      $('#notesModal .modal-body').html(res.html || res);
      $('#notesModal').modal('show');
    }).fail(function(){
      alert('Failed to load note.');
    });
  });

  // Delete note
  // $(document).on('click', '.notes-delete', function () {
  //   if (!confirm('Delete?')) return;
  //   const $btn = $(this);
  //   const $c = $btn.closest('.notes-component');
  //   const $filt = $c.find('.notes-filter-form');

  //   $.post(`${api}/delete/${$btn.data('id')}`, {
  //     _token: '{{ csrf_token() }}'
  //   }, _ => load($c, Object.fromEntries(new URLSearchParams($filt.serialize()))));
  // });
  $(function(){
    let pendingDelete = null;
    let pendingDeleteURL = null;
    let pendingDeleteMessage = null;

    // When user clicks a delete button, open the modal
    $(document).on('click', '.notes-delete', function(){
      pendingDelete = $(this).data('id');
      pendingDeleteURL = $(this).data('url');
      pendingDeleteMessage = $(this).data('message') || 'Are you sure you want to delete this item?';
      $('#deleteConfirmModal .modal-body .delete-message').text(pendingDeleteMessage);
      $('#deleteConfirmModal').modal('show');
    });

    // When user confirms deletion
    $(document).on('click','#confirmDeleteBtn', function(){
      if (!pendingDelete) return;
      if (!pendingDeleteURL) return;
      // Find the component context
      const $btn = $(`.notes-delete[data-id="${pendingDelete}"]`);
      const $comp = $btn.closest('.notes-component');
      const $filt = $comp.find('.notes-filter-form');

      $.post(pendingDeleteURL, {
            _token: $('meta[name="csrf-token"]').attr('content')
      }).done(function (response) {
        // Hide modal
        $('#deleteConfirmModal').modal('hide');
        // Refresh list
        const params = Object.fromEntries(new URLSearchParams($filt.serialize()));
        load($comp, params);
        pendingDelete = null;
      }).fail(function(){
        alert('Deletion failed. Please try again.');
        $('#deleteConfirmModal').modal('hide');
      });
    });

    // Clear pending if modal closed
    $('#deleteConfirmModal').on('hidden.bs.modal', function(){
      pendingDelete = null;
    });
  });

  // Save note form (modal submit)
  // $(document).on('submit', '.notes-form', function(e) {
  //   e.preventDefault();
  //   initValidate(this);
  //   const $form = $(this);
  //   const $c = $form.closest('.notes-component');
  //   const $modal = $c.find('.notes-modal');
  //   const $filt = $c.find('.notes-filter-form');

  //   $.post(`${api}/save`, $form.serialize(), r => {
  //     $modal.modal('hide');
  //     load($c, Object.fromEntries(new URLSearchParams($filt.serialize())));
  //   });
  // });

  $(document).on('submit', '#notesForm', function(e) {
    e.preventDefault();              // stop the normal form submit

    const $form = $(this);
    const $c    = $form.closest('.notes-component');
    const $modal = $('#notesModal'); // or $form.closest('.notes-modal')
    const $filt  = $c.find('.notes-filter-form');

    // disable the save button to prevent double-click
    const $btn = $form.find('.notes-save').prop('disabled', true);

    $.ajax({
      url:   $form.attr('action'),    // '/admin/notes/save'
      method:'POST',
      data:  $form.serialize(),
    }).done(function(res) {
      // you can inspect res.status or res.message if you return JSON
      $modal.modal('hide');
      load($c, Object.fromEntries(new URLSearchParams($filt.serialize())));
    }).fail(function(xhr) {
      // handle validation errors (xhr.responseJSON.errors)
      let msg = 'Failed to save note.';
      if (xhr.responseJSON && xhr.responseJSON.errors) {
        msg = Object.values(xhr.responseJSON.errors).flat().join("\n");
      }
      alert(msg);
    }).always(function() {
      $btn.prop('disabled', false);
    });
  });


  // Pagination click
  $(document).on('click', '.notes-component .pagination a', function(e){
    e.preventDefault();
    const $a = $(this);
    const $c = $a.closest('.notes-component');
    const url = new URL($a.attr('href'), location.origin);
    const params = Object.fromEntries(url.searchParams.entries());
    load($c, params);
  });

  $(document).on('hidden.bs.modal', '.notes-modal', function () {
    const $f = $(this).find('form')[0];
    if ($f) $f.reset();
    $(this).find('.modal-body').empty(); // Clear form fields and view content
  });

})(jQuery);
