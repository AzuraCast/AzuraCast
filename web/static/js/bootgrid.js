$(function() {
    // Make jQuery Bootgrid compatible with Bootstrap 4
    $.extend($.fn.bootgrid.Constructor.defaults.css, {
        iconRefresh: "refresh",
        iconColumns: "list",
        iconSearch: "search",
        iconDown: "expand_more",
        iconUp: "expand_less",
        dropDownMenuItems: "dropdown-menu dropdown-menu-right",
        paginationButton: "page-link"
    });

    $.extend($.fn.bootgrid.Constructor.defaults.templates, {
        icon: "<i class=\"material-icons\">{{ctx.iconCss}}</i>",
        paginationItem: "<li class=\"paginate_button page-item {{ctx.css}}\"><a data-page=\"{{ctx.page}}\" class=\"{{css.paginationButton}}\">{{ctx.text}}</a></li>",
        footer: "<div id=\"{{ctx.id}}\" class=\"{{css.footer}}\"><div class=\"row\"><div class=\"col-lg-6\"><p class=\"{{css.pagination}}\"></p></div><div class=\"col-lg-6 infoBar\"><p class=\"{{css.infos}}\"></p></div></div></div>"
    });
});
