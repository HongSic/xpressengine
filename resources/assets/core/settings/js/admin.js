import 'bootstrap'
import $ from 'jquery'

$(document).ready(function () {
  var $sidebar = $('.settings-nav-sidebar')
  var $dim = $('.dim')

  /* 사이드바 */
  $sidebar.on('setting.sidebar.open', function () {
    $sidebar.addClass('open')
    $dim.show()
    $('body').css('overflow', 'hidden')
    $('html').css('position', 'fixed')
  }).on('setting.sidebar.close', function () {
    $sidebar.removeClass('open')
    $dim.hide()
    $('body').css('overflow', '')
    $('html').css('position', '')
  }).on('setting.sidebar.toggle', function () {
    if ($(window).innerWidth() < 1068) {
      $('body').removeClass('sidebar-collapse')
      if ($sidebar.hasClass('open')) {
        $sidebar.trigger('setting.sidebar.close')
      } else {
        $sidebar.trigger('setting.sidebar.open')
      }
    } else {
      $('body').toggleClass('sidebar-collapse')
    }
  })

  $dim.on('click', function () {
    $sidebar.trigger('setting.sidebar.close')
  })

  $('.btn-slide').on('click', function () {
    $sidebar.trigger('setting.sidebar.toggle')
  })

  /* 사이드바 메뉴 */
  $(document).on('click', '.snb-list li', function (event) {
    var $target = $(event.target)
    var $subdepth = $target.closest('.sub-depth')
    var $ul

    if ($.inArray('__xe_collapseMenu', $target[0].classList) > -1) {
      $ul = $target.siblings('.sub-depth-list')
    } else {
      $ul = $target.parent().siblings('.sub-depth-list')
    }

    if ($ul.length === 0) {
      return true
    }

    if ($ul.is(':visible')) {
      $ul.find('.sub-depth-list').slideUp('fast')
      $ul.find('.sub-depth').removeClass('open')

      $ul.slideUp('fast')
      $subdepth.removeClass('open')
    } else {
      var $parent = $subdepth.parent()

      // $parent.find('.sub-depth.open>.sub-depth-list').slideUp('fast');
      $parent.find('.sub-depth.open').removeClass('open')

      $ul.slideDown('fast')
      $subdepth.addClass('open')
    }

    return false
  })

  /* notice 닫기 버튼 */
  $('.notice__button-close').click(function () {
    $(this).parent('.notice').remove();
  });
})
