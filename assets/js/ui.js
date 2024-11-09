let searchTimeout;

let setFocusOnFirstVisibleProject = function () {
    $('.dropdown-projects-list .project-item.focus').removeClass('focus');
    $('.dropdown-projects-list .project-item:visible').first().addClass('focus');
};

let searchProject = function () {
    let query = $('.project-dropdown-menu input').val().toLowerCase();

    $('.project-dropdown-menu .project-group-node').each(function () {
        let name = $(this).children('.project-group-node-header').children('.project-group-node-label').text().toLowerCase();

        if (name.indexOf(query) !== -1) {
            $(this).removeClass('d-none');
        } else {
            $(this).addClass('d-none');
        }
    });

    let visibleNodes = $('.project-dropdown-menu .project-group-node:not(.d-none)');
    visibleNodes.find('.project-group-node').removeClass('d-none');
    visibleNodes.parents().removeClass('d-none');

    if ($('.project-dropdown-menu .project-item:visible').length) {
        setFocusOnFirstVisibleProject();
        $('.dropdown-item.no-projects-found').addClass('d-none');
    } else {
        $('.dropdown-item.no-projects-found').removeClass('d-none');
    }

    searchTimeout = null;
};

$(document).ready(function () {
    let dropdownEl = $('.project-dropdown-toggle');
    let inputEl = $('.project-dropdown-menu input');

    dropdownEl.click(function () {
        if ($(this).hasClass('show')) {
            dropdownEl.dropdown('hide');
        } else {
            dropdownEl.dropdown('show');
            inputEl.focus();
            if (inputEl.val().trim() === '') {
                searchProject();
            }

            setFocusOnFirstVisibleProject();
            $('.dropdown-projects-list .project-item.focus')[0].scrollIntoView({ block: 'nearest' });
        }
    });

    $('body').click(function (ev) {
        if (!$(ev.target).parents('.project-dropdown').length && dropdownEl.hasClass('show')) {
            dropdownEl.dropdown('hide');
            $('.project-dropdown-menu input').val('');
            searchProject();
        }
    });

    inputEl.on('keydown', function (ev) {
        if (ev.keyCode === 38 || ev.keyCode === 40 || ev.keyCode === 13) {
            let focusedProject = $('.dropdown-projects-list .project-item.focus');
            if (ev.keyCode === 38) {
                let prevProject = focusedProject.prevAll(":visible:first");
                if (prevProject.length) {
                    focusedProject.removeClass('focus');
                    prevProject.addClass('focus');
                    prevProject[0].scrollIntoView({ block: 'nearest' });
                }
            } else if (ev.keyCode === 40) {
                let nextProject = focusedProject.nextAll(':visible:first');
                if (nextProject.length) {
                    focusedProject.removeClass('focus');
                    nextProject.addClass('focus');
                    nextProject[0].scrollIntoView({ block: 'nearest' });
                }
            } else if (ev.keyCode === 13) {
                window.location.href = focusedProject.attr('href');
            }

            ev.stopPropagation();
            ev.preventDefault(false);

            return;
        }

        if (searchTimeout !== null) {
            clearTimeout(searchTimeout);
            searchTimeout = null;
        }

        searchTimeout = setTimeout(searchProject, 250);
    }).on('change', function () {
        if (dropdownEl.hasClass('show')) {
            if (searchTimeout !== null) {
                clearTimeout(searchTimeout);
                searchTimeout = null;
            }

            searchProject();
            $('.project-dropdown-menu input').focus();
        };
    });
});