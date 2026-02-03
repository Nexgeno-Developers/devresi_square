(function($){
  const api = '/admin/documents';

  // Load document list
  function load($c, extraParams = {}) {
    const type = $c.data('documentable-type');
    const id   = $c.data('documentable-id');
    const $list = $c.find('.documents-list');

    $list.html('<p>Loading…</p>');
    $.get(`${api}/list`, {
      documentable_type: type,
      documentable_id: id,
      ...extraParams
    }, r => {
      $list.html(r.html);
    });
  }

  // Open add/edit document form
  function loadForm(url, title) {
    $.get(url, function(html){
      $('#documentsModal .modal-title').text(title);
      $('#documentsModal .modal-body').html(html);
      AIZ.uploader.previewGenerate();
      $('#documentsModal').modal('show');
    }).fail(function(){
      alert('Failed to load form.');
    });
  }

  // Initialize components
  $(function(){
    $('.documents-component').each(function(){
      load($(this));
    });
  });

  // Handle filter form submit
  $(document).on('submit', '.documents-filter-form', function(e) {
    e.preventDefault();
    const $form = $(this);
    const $c = $form.closest('.documents-component');
    const formData = Object.fromEntries(new URLSearchParams($form.serialize()));
    load($c, formData);
  });

  // Handle reset button
  $(document).on('click', '.documents-reset', function () {
    const $btn = $(this);
    const $c = $btn.closest('.documents-component');
    const $filt = $c.find('.documents-filter-form');
    $filt[0].reset();
    load($c);
  });

  // Add button
  $(document).on('click', '.documents-add', function(){
    const $comp = $(this).closest('.documents-component');
    const type = encodeURIComponent($comp.data('documentable-type'));
    const id   = $comp.data('documentable-id');
    const url  = `${api}/create?documentable_type=${type}&documentable_id=${id}`;
    loadForm(url, 'Add Document');
  });

  // Edit button
  $(document).on('click', '.documents-edit', function(){
    const documentId = $(this).data('id');
    const url    = `${api}/${documentId}/edit`;
    loadForm(url, 'Edit Document');
  });

  // View button
  $(document).on('click', '.documents-view', function(){
    const documentId = $(this).data('id');
    const url    = `${api}/show/${documentId}`;
    $.get(url, function(res){
      $('#documentsModal .modal-title').text('View document');
      $('#documentsModal .modal-body').html(res.html || res);
      AIZ.uploader.previewGenerate();
      $('#documentsModal').modal('show');
    }).fail(function(){
      alert('Failed to load document.');
    });
  });

  // Delete document
  // $(document).on('click', '.documents-delete', function () {
  //   if (!confirm('Delete?')) return;
  //   const $btn = $(this);
  //   const $c = $btn.closest('.documents-component');
  //   const $filt = $c.find('.documents-filter-form');

  //   $.post(`${api}/delete/${$btn.data('id')}`, {
  //     _token: '{{ csrf_token() }}'
  //   }, _ => load($c, Object.fromEntries(new URLSearchParams($filt.serialize()))));
  // });
  $(function(){
    let pendingDelete = null;
    let pendingDeleteURL = null;
    let pendingDeleteMessage = null;

    // When user clicks a delete button, open the modal
    $(document).on('click', '.documents-delete', function(){
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
      const $btn = $(`.documents-delete[data-id="${pendingDelete}"]`);
      const $comp = $btn.closest('.documents-component');
      const $filt = $comp.find('.documents-filter-form');

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

  $(document).on('submit', '#documentsForm', function(e) {
    e.preventDefault();              // stop the normal form submit

    const $form = $(this);
    const $c    = $form.closest('.documents-component');
    const $modal = $('#documentsModal'); // or $form.closest('.documents-modal')
    const $filt  = $c.find('.documents-filter-form');

    // disable the save button to prevent double-click
    const $btn = $form.find('.documents-save').prop('disabled', true);

    $.ajax({
      url:   $form.attr('action'),    // '/admin/documents/save'
      method:'POST',
      data:  $form.serialize(),
    }).done(function(res) {
      // you can inspect res.status or res.message if you return JSON
      $modal.modal('hide');
      load($c, Object.fromEntries(new URLSearchParams($filt.serialize())));
    }).fail(function(xhr) {
      // handle validation errors (xhr.responseJSON.errors)
      let msg = 'Failed to save document.';
      if (xhr.responseJSON && xhr.responseJSON.errors) {
        msg = Object.values(xhr.responseJSON.errors).flat().join("\n");
      }
      alert(msg);
    }).always(function() {
      $btn.prop('disabled', false);
    });
  });


  // Pagination click
  $(document).on('click', '.documents-component .pagination a', function(e){
    e.preventDefault();
    const $a = $(this);
    const $c = $a.closest('.documents-component');
    const url = new URL($a.attr('href'), location.origin);
    const params = Object.fromEntries(url.searchParams.entries());
    load($c, params);
  });

  $(document).on('hidden.bs.modal', '.documents-modal', function () {
    const $f = $(this).find('form')[0];
    if ($f) $f.reset();
    $(this).find('.modal-body').empty(); // Clear form fields and view content
  });

})(jQuery);
