$(document).ready(function () {
    $('body').on('click', '.clickable-table-row', function (ev) {
        let targetEl = $(ev.target);
        if (targetEl.is('button, a') || targetEl.parent().is('button, a')) {
            return;
        }

        window.location.href = $(this).data('href');
    });

    $('body').on('click', '.project-group-node .clickable', function () {
        $(this).parent().parent().toggleClass('open');
    });

    $('.project-group-tree input:checked').each(function () {
        $(this).parent().parent().parent().parent().parents('.project-group-node').addClass('open');
    });

    $('.project-group-tree .project-group-node.active-project').each(function () {
        $(this).parent().parents('.project-group-node').addClass('open');
    });
});