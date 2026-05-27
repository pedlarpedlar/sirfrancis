"use strict";

(function ($) {
  "use strict"; //  Searching & Expand Menu Popup

  $(".close").on("click", function () {
    $("body").removeClass("open");
  }); // AOS.init({
  //   // Global settings:
  //   disable: false, // accepts following values: 'phone', 'tablet', 'mobile', boolean, expression or function
  //   startEvent: 'DOMContentLoaded', // name of the event dispatched on the document, that AOS should initialize on
  //   initClassName: 'aos-init', // class applied after initialization
  //   animatedClassName: 'aos-animate', // class applied on animation
  //   useClassNames: false, // if true, will add content of `data-aos` as classes on scroll
  //   disableMutationObserver: false, // disables automatic mutations' detections (advanced)
  //   debounceDelay: 50, // the delay on debounce used while resizing window (advanced)
  //   throttleDelay: 99, // the delay on throttle used while scrolling the page (advanced)
  //   // Settings that can be overridden on per-element basis, by `data-aos-*` attributes:
  //   offset: 120, // offset (in px) from the original trigger point
  //   delay: 0, // values from 0 to 3000, with step 50ms
  //   duration: 400, // values from 0 to 3000, with step 50ms
  //   easing: 'ease-in-out', // default easing for AOS animations
  //   once: true, // whether animation should happen only once - while scrolling down
  //   mirror: false, // whether elements should animate out while scrolling past them
  //   anchorPlacement: 'top-bottom', // defines which position of the element regarding to window should trigger the animation
  // });

  /*---------------------------
          Commons Variables
       ------------------------------ */

  var $window = $(window),
      $body = $("body");
  /*--------------------------
      Sticky Menu
    ---------------------------- */

  $($window).on("scroll", function () {
    var scroll = $($window).scrollTop();

    if (scroll < 150) {
      $("#sticky").removeClass("is-isticky");
    } else {
      $("#sticky").addClass("is-isticky");
    }
  });

  /*----------------------------------
           Off Canvas Menu
       -----------------------------------*/

  function mobileOffCanvasMenu() {
    var $offCanvasNav = $(".offcanvas-menu, .overlay-menu"),
        $offCanvasNavSubMenu = $offCanvasNav.find(".offcanvas-submenu");
    /*Add Toggle Button With Off Canvas Sub Menu*/

    $offCanvasNavSubMenu.parent().prepend('<span class="menu-expand"></span>');
    /*Category Sub Menu Toggle*/

    $offCanvasNav.on("click", "li a, .menu-expand", function (e) {
      var $this = $(this);

      if ($this.attr("href") === "#" || $this.hasClass("menu-expand")) {
        e.preventDefault();

        if ($this.siblings("ul:visible").length) {
          $this.parent("li").removeClass("active");
          $this.siblings("ul").slideUp();
          $this.parent("li").find("li").removeClass("active");
          $this.parent("li").find("ul:visible").slideUp();
        } else {
          $this.parent("li").addClass("active");
          $this.closest("li").siblings("li").removeClass("active").find("li").removeClass("active");
          $this.closest("li").siblings("li").find("ul:visible").slideUp();
          $this.siblings("ul").slideDown();
        }
      }
    });
  }

  mobileOffCanvasMenu();
  var $offcanvasMenu2 = $("#offcanvas-menu2 li a");
  $offcanvasMenu2.on("click", function (e) {
    // e.preventDefault();
    $(this).closest("li").toggleClass("active");
    $(this).closest("li").siblings().removeClass("active");
    $(this).closest("li").siblings().children(".category-sub-menu").slideUp();
    $(this).closest("li").siblings().children(".category-sub-menu").children("li").toggleClass("active");
    $(this).closest("li").siblings().children(".category-sub-menu").children("li").removeClass("active");
    $(this).parent().children(".category-sub-menu").slideToggle();
  });
  /*-----------------------------
        main slider active
      ---------------------------- */

  var $mainSlider = $(".main-slider");
  $mainSlider.slick({
    autoplay: true,
    autoplaySpeed: 6000,
    speed: 800,
    slidesToShow: 1,
    slidesToScroll: 1,
    dots: true,
    fade: true,
    arrows: true,
    prevArrow: '<button class="slick-prev"><i class="fas fa-chevron-left"></i></button>',
    nextArrow: '<button class="slick-next"><i class="fas fa-chevron-right"></i></button>',
    responsive: [{
      breakpoint: 767,
      settings: {
        dots: true,
        arrows: false
      }
    }]
  }).slickAnimation();
  /*--------------------------
         product slider init
        ---------------------------- */

  var $productSliderInit = $(".product-slider-init");
  $productSliderInit.slick({
    autoplay: false,
    autoplaySpeed: 10000,
    dots: false,
    infinite: false,
    arrows: true,
    speed: 1000,
    slidesToShow: 4,
    slidesToScroll: 1,
    prevArrow: '<button class="slick-prev"><i class="ion-chevron-left"></i></button>',
    nextArrow: '<button class="slick-next"><i class="ion-chevron-right"></i></button>',
    responsive: [{
      breakpoint: 1199,
      settings: {
        slidesToShow: 3,
        slidesToScroll: 1,
        infinite: true,
        dots: false
      }
    }, {
      breakpoint: 1024,
      settings: {
        slidesToShow: 3,
        slidesToScroll: 1,
        arrows: true,
        autoplay: true
      }
    }, {
      breakpoint: 768,
      settings: {
        slidesToShow: 2,
        slidesToScroll: 1,
        arrows: false,
        autoplay: true
      }
    }, {
      breakpoint: 480,
      settings: {
        slidesToShow: 1,
        slidesToScroll: 1,
        arrows: false,
        autoplay: true
      }
    } // You can unslick at a given breakpoint now by adding:
    // settings: "unslick"
    // instead of a settings object
    ]
  });
  /*--------------------------
         popular-slider-init
        ---------------------------- */

  var $popularSlider = $(".popular-slider-init");
  $popularSlider.slick({
    autoplay: false,
    autoplaySpeed: 10000,
    dots: true,
    infinite: false,
    arrows: false,
    speed: 1000,
    slidesToShow: 5,
    slidesToScroll: 1,
    prevArrow: '<button class="slick-prev"><i class="ion-chevron-left"></i></button>',
    nextArrow: '<button class="slick-next"><i class="ion-chevron-right"></i></button>',
    responsive: [{
      breakpoint: 1280,
      settings: {
        slidesToShow: 4,
        slidesToScroll: 1,
        infinite: false,
        dots: true
      }
    }, {
      breakpoint: 991,
      settings: {
        slidesToShow: 3,
        slidesToScroll: 1,
        arrows: false,
        autoplay: true
      }
    }, {
      breakpoint: 768,
      settings: {
        slidesToShow: 2,
        slidesToScroll: 1,
        arrows: false,
        autoplay: true
      }
    }, {
      breakpoint: 480,
      settings: {
        slidesToShow: 1,
        slidesToScroll: 1,
        arrows: false,
        autoplay: true
      }
    } // You can unslick at a given breakpoint now by adding:
    // settings: "unslick"
    // instead of a settings object
    ]
  });
  /*--------------------------
        featured-init
  ---------------------------- */

  var $featuredSlider = $(".featured-init");
  $featuredSlider.slick({
    autoplay: false,
    autoplaySpeed: 10000,
    dots: false,
    infinite: false,
    arrows: true,
    speed: 1000,
    slidesToShow: 4,
    slidesToScroll: 1,
    prevArrow: '<button class="slick-prev"><i class="ion-chevron-left"></i></button>',
    nextArrow: '<button class="slick-next"><i class="ion-chevron-right"></i></button>',
    responsive: [{
      breakpoint: 1280,
      settings: {
        slidesToShow: 3,
        slidesToScroll: 1,
        infinite: false,
        dots: false
      }
    }, {
      breakpoint: 991,
      settings: {
        slidesToShow: 2,
        slidesToScroll: 1,
        arrows: true,
        autoplay: true
      }
    }, {
      breakpoint: 768,
      settings: {
        slidesToShow: 2,
        slidesToScroll: 1,
        arrows: true,
        autoplay: true
      }
    }, {
      breakpoint: 480,
      settings: {
        slidesToShow: 1,
        slidesToScroll: 1,
        arrows: false,
        autoplay: true
      }
    } // You can unslick at a given breakpoint now by adding:
    // settings: "unslick"
    // instead of a settings object
    ]
  });
  /*--------------------------
         product ctry slider init
        ---------------------------- */

  var $productCtry = $(".product-ctry-init");
  $productCtry.slick({
    autoplay: false,
    autoplaySpeed: 10000,
    dots: false,
    infinite: false,
    arrows: true,
    speed: 1000,
    slidesToShow: 1,
    slidesToScroll: 1,
    prevArrow: '<button class="slick-prev"><i class="ion-chevron-left"></i></button>',
    nextArrow: '<button class="slick-next"><i class="ion-chevron-right"></i></button>',
    responsive: [{
      breakpoint: 1024,
      settings: {
        slidesToShow: 1,
        slidesToScroll: 1,
        infinite: true,
        dots: false
      }
    }, {
      breakpoint: 992,
      settings: {
        slidesToShow: 2,
        slidesToScroll: 1,
        arrows: true,
        autoplay: true
      }
    }, {
      breakpoint: 767,
      settings: {
        slidesToShow: 1,
        slidesToScroll: 1,
        arrows: false,
        autoplay: true
      }
    } // You can unslick at a given breakpoint now by adding:
    // settings: "unslick"
    // instead of a settings object
    ]
  });
  /*--------------------------
         blog slider init
        ---------------------------- */

  var $blogInit = $(".blog-init");
  $blogInit.slick({
    autoplay: false,
    autoplaySpeed: 10000,
    dots: false,
    infinite: false,
    arrows: true,
    speed: 1000,
    slidesToShow: 4,
    slidesToScroll: 1,
    prevArrow: '<button class="slick-prev"><i class="ion-chevron-left"></i></button>',
    nextArrow: '<button class="slick-next"><i class="ion-chevron-right"></i></button>',
    responsive: [{
      breakpoint: 1024,
      settings: {
        slidesToShow: 3,
        slidesToScroll: 1,
        infinite: true,
        dots: false
      }
    }, {
      breakpoint: 991,
      settings: {
        slidesToShow: 2,
        slidesToScroll: 1,
        arrows: true,
        autoplay: true
      }
    }, {
      breakpoint: 767,
      settings: {
        slidesToShow: 1,
        slidesToScroll: 1,
        arrows: false,
        autoplay: true
      }
    }, {
      breakpoint: 575,
      settings: {
        slidesToShow: 1,
        slidesToScroll: 1,
        arrows: false,
        autoplay: true
      }
    } // You can unslick at a given breakpoint now by adding:
    // settings: "unslick"
    // instead of a settings object
    ]
  });
  /*--------------------------
         brand slider init
        ---------------------------- */

  var $brandInit = $(".brand-init");
  $brandInit.slick({
    autoplay: false,
    autoplaySpeed: 10000,
    dots: false,
    infinite: false,
    arrows: true,
    speed: 1000,
    slidesToShow: 6,
    slidesToScroll: 1,
    prevArrow: '<button class="slick-prev"><i class="ion-chevron-left"></i></button>',
    nextArrow: '<button class="slick-next"><i class="ion-chevron-right"></i></button>',
    responsive: [{
      breakpoint: 1024,
      settings: {
        slidesToShow: 5,
        slidesToScroll: 1,
        infinite: true,
        dots: false
      }
    }, {
      breakpoint: 991,
      settings: {
        slidesToShow: 4,
        slidesToScroll: 1,
        arrows: true,
        autoplay: true
      }
    }, {
      breakpoint: 767,
      settings: {
        slidesToShow: 3,
        slidesToScroll: 1,
        arrows: false,
        autoplay: true
      }
    }, {
      breakpoint: 575,
      settings: {
        slidesToShow: 2,
        slidesToScroll: 1,
        arrows: false,
        autoplay: true
      }
    } // You can unslick at a given breakpoint now by adding:
    // settings: "unslick"
    // instead of a settings object
    ]
  });
  /*---------------------------
      countdown-syncing
      ---------------------------- */

  $(".countdown-sync-init").slick({
    slidesToShow: 1,
    slidesToScroll: 1,
    infinite: true,
    draggable: false,
    arrows: false,
    dots: false,
    fade: true,
    asNavFor: ".countdown-sync-nav"
  });
  $(".countdown-sync-nav").slick({
    dots: false,
    arrows: false,
    infinite: true,
    prevArrow: '<button class="slick-prev"><i class="fas fa-arrow-left"></i></button>',
    nextArrow: '<button class="slick-next"><i class="fas fa-arrow-right"></i></button>',
    slidesToShow: 3,
    slidesToScroll: 1,
    asNavFor: ".countdown-sync-init",
    focusOnSelect: true,
    draggable: false
  });
  /*---------------------------
      product-syncing
      ---------------------------- */

  $(".product-sync-init").slick({
    slidesToShow: 1,
    slidesToScroll: 1,
    infinite: true,
    draggable: false,
    arrows: false,
    dots: false,
    fade: true,
    asNavFor: ".product-sync-nav"
  });
  $(".product-sync-nav").slick({
    dots: false,
    arrows: false,
    infinite: true,
    prevArrow: '<button class="slick-prev"><i class="fas fa-arrow-left"></i></button>',
    nextArrow: '<button class="slick-next"><i class="fas fa-arrow-right"></i></button>',
    slidesToShow: 4,
    slidesToScroll: 1,
    asNavFor: ".product-sync-init",
    focusOnSelect: true,
    draggable: false
  });
  /*--------------------------
      tooltip
      ---------------------------- */

  $('[data-toggle="tooltip"]').tooltip(); // slider-range

  $("#slider-range").slider({
    range: true,
    min: 0,
    max: 800,
    values: [200, 600],
    slide: function slide(event, ui) {
      $("#amount").val("R" + ui.values[0] + " - R" + ui.values[1]);
    }
  });
  $("#amount").val("R" + $("#slider-range").slider("values", 0) + " - R" + $("#slider-range").slider("values", 1)); // slider-range end

  /*----------------------------------------
      fixed issue in bootstrap tabs problem
      ----------------------------------------- */

  $('a[data-toggle="pill"]').on("shown.bs.tab", function (e) {
    e.target;
    e.relatedTarget;
    $(".slick-slider").slick("setPosition");
  });
  /*-----------------------------------
       fixed issue in bs modal problem
       ---------------------------------- */

  $(".modal").on("shown.bs.modal", function (e) {
    $(".slick-slider").slick("setPosition");
  });
  /*--------------------------
      comment  scroll down 
      ---------------------------- */

  $("#write-comment").on("click", function (e) {
    e.preventDefault();
    $("html, body").animate({
      scrollTop: $(".btn-dark ").offset().top + 750
    }, 500, "linear");
  });
  /*--------------------------     
           counter 
         -------------------------- */

  $('body').on('click', '.count .increment, .count .decrement', function () {
      var count = $(this).closest('.count'),
          input = count.find('input[type="number"]'),
          minValue = parseFloat(input.attr("min")),
          maxValue = parseFloat(input.attr("max")),
          oldValue = parseFloat(input.val());

      var isIncrement = $(this).hasClass('increment');
      var incrementValue = isIncrement ? 1 : -1;

      var newVal = oldValue + incrementValue;

      // Ensure the new value is within the specified range
      newVal = Math.min(Math.max(newVal, minValue), maxValue);

      count.find("input").val(newVal);
      count.find("input").trigger("change");
  });

  /*-------------------------
    Create an account toggle
    --------------------------*/

  $(".checkout-toggle2").on("click", function () {
    $(".open-toggle2").slideToggle(1000);
  });
  $(".checkout-toggle").on("click", function () {
    $(".open-toggle").slideToggle(1000);
  });
  /*--------------------------
      SscrollUp
    ---------------------------- */

  // $.scrollUp({
  //   scrollName: "scrollUp",
  //   // Element ID
  //   scrollDistance: 400,
  //   // Distance from top/bottom before showing element (px)
  //   scrollFrom: "top",
  //   // 'top' or 'bottom'
  //   scrollSpeed: 800,
  //   // Speed back to top (ms)
  //   easingType: "linear",
  //   // Scroll to top easing (see http://easings.net/)
  //   animation: "fade",
  //   // Fade, slide, none
  //   animationSpeed: 400,
  //   // Animation speed (ms)
  //   scrollTrigger: false,
  //   // Set a custom triggering element. Can be an HTML string or jQuery object
  //   scrollTarget: false,
  //   // Set a custom target element for scrolling to. Can be element or number
  //   scrollText: '<i class="fas fa-arrow-up"></i>',
  //   // Text for element, can contain HTML
  //   scrollTitle: false,
  //   // Set a custom <a> title if required.
  //   scrollImg: false,
  //   // Set true to use image
  //   activeOverlay: false,
  //   // Set CSS color to display scrollUp active point, e.g '#00FFFF'
  //   zIndex: 214 // Z-Index for the overlay

  // });

$.scrollUp({
    scrollName: "scrollUp",
    scrollDistance: 400,
    scrollFrom: "top",
    scrollSpeed: 800,
    easingType: "linear",
    animation: "fade",
    animationSpeed: 400,
    scrollTrigger: false,
    scrollTarget: false,
    scrollText: '<img src="https://www.candybird.co.za/assets/img/arrow.svg" width="20px"/>',
    // Using a Font Awesome anchor icon
    scrollTitle: false,
    scrollImg: false,
    activeOverlay: false,
    zIndex: 214
});


})(jQuery);