window.jQuery(function ($) {
  $('.skin-setting-list .__xe_assign-btn').click(function () {
    var $btn = $(this)
    var url = $btn.data('skinAssignLink')
    XE.ajax({
      type: 'put',
      url: url,
      dataType: 'json',
      success: function (data) {
        window.XE.toast(data.type, data.message)
        var type = $btn.data('skinType')
        if (type == 'desktop') {
          $btn.parents('.skin-setting-list').find('.__xe_skin-desktop').removeClass('on')
        } else {
          $btn.parents('.skin-setting-list').find('.__xe_skin-mobile').removeClass('on')
        }

        $btn.addClass('on')
      },

      error: function (data) {
        window.XE.toast(data.type, data.message)
      }
    })
  })

  // $('.skin-setting-list .__xe_skin-setting-btn').click(function(){
  //   var url = $(this).data('url');
  //   modalPage(url)
  // })

  $(document).on('submit', 'form.__xe_skin_form', function () {
    var $form = $(this)
    var $modal = $('#skinModal')
    var formdata = new FormData(this) || null

    XE.ajax({
      type: $form.attr('method'),
      url: $form.attr('action'),
      enctype: 'multipart/form-data',
      cache: false,
      data: formdata,
      dataType: 'json',
      contentType: false,
      processData: false,
      success: function (data) {
        $modal.xeModal('hide')
        window.XE.toast(data.type, data.message)
      },

      error: function (data) {
        window.XE.toast(data.type, data.message)
      }
    })
    return false
  })

  function modalPage (url, callback) {
    var $modal = $('#skinModal')
    XE.ajax({
      url: url,
      type: 'get',
      dataType: 'json',
      success: function (data) {
        $modal.find('.modal-content').empty().html(data.view)
        $modal.xeModal()
        if (callback) {
          callback(data)
        }
      }
    })
  }
})
