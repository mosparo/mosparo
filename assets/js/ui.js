let searchTimeout;

let setFocusOnFirstVisibleProject = function () {
    $('.dropdown-projects-list .project-group-node.focus').removeClass('focus');
    $('.dropdown-projects-list .project-group-node:visible').first().addClass('focus');
};

let searchProject = function () {
    let query = $('.project-dropdown-menu input').val().toLowerCase();

    $('.project-dropdown-menu .project-group-node:not(.disabled)').each(function () {
        let name = $(this).children('.project-group-node-header').children('.project-group-node-label').text().toLowerCase();

        if (name.indexOf(query) !== -1) {
            $(this).removeClass('d-none');
        } else {
            $(this).addClass('d-none');
        }
    });

    let visibleNodes = $('.project-dropdown-menu .project-group-node:not(.d-none):not(.disabled)');
    visibleNodes.find('.project-group-node').removeClass('d-none');
    visibleNodes.parents().removeClass('d-none');

    if ($('.project-dropdown-menu .project-group-node:visible:not(.disabled)').length) {
        setFocusOnFirstVisibleProject();
        $('.dropdown-projects-list .no-search-results-found').addClass('d-none');
    } else {
        $('.dropdown-projects-list .no-search-results-found').removeClass('d-none');
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
            $('.dropdown-projects-list .project-group-node.focus')[0].scrollIntoView({ block: 'nearest' });
        }
    });

    $('body').click(function (ev) {
        if (!$(ev.target).parents('.project-dropdown').length && dropdownEl.hasClass('show')) {
            dropdownEl.dropdown('hide');
            $('.project-dropdown-menu input').val('');
            searchProject();
        }
    });

    function getPreviousVisibleNode(el)
    {
        let prevEl = el.prevAll(':not(.focus):not(.disabled):visible:first');
        if (!prevEl.length && !el.parent().hasClass('project-group-tree')) {
            prevEl = el.parent().parent();
        }

        let childrenEl = prevEl.children('.project-group-node-children');
        if (prevEl.get(0) !== el.parent().parent().get(0) && prevEl.hasClass('open') && childrenEl.length && childrenEl.children(':visible:not(.disabled)').length) {
            prevEl = findLast(childrenEl.children(':visible:not(.disabled):last'));
        }

        return prevEl;
    }

    function findLast(el)
    {
        let childrenEl = el.children('.project-group-node-children');
        if (el.hasClass('open') && childrenEl.length && childrenEl.children(':visible:not(.disabled)').length) {
            el = findLast(childrenEl.children(':visible:not(.disabled):last'));
        }

        return el;
    }

    function getNextVisibleNode(el, noRecursive)
    {
        let nextEl = el.nextAll(':visible:not(.disabled):first');
        let childrenEl = el.children('.project-group-node-children');
        if (!noRecursive && el.hasClass('open') && childrenEl.length && childrenEl.children(':visible:not(.disabled)').length) {
            nextEl = childrenEl.children(':visible:not(.disabled):first');
        }

        if (!nextEl.length && !el.parent().hasClass('project-group-tree')) {
            nextEl = getNextVisibleNode(el.parent().parent(), true);
        }

        return nextEl;
    }

    inputEl.on('keydown', function (ev) {
        if (ev.keyCode === 38 || ev.keyCode === 40 || ev.keyCode === 13) {
            let focusedNode = $('.dropdown-projects-list .project-group-node.focus');
            if (ev.keyCode === 38) {
                let prevNode = getPreviousVisibleNode(focusedNode);
                if (prevNode.length) {
                    focusedNode.removeClass('focus');
                    prevNode.addClass('focus');
                    prevNode[0].scrollIntoView({ block: 'nearest' });
                }
            } else if (ev.keyCode === 40) {
                let nextNode = getNextVisibleNode(focusedNode);
                if (nextNode.length) {
                    focusedNode.removeClass('focus');
                    nextNode.addClass('focus');
                    nextNode[0].scrollIntoView({ block: 'nearest' });
                }
            } else if (ev.keyCode === 13) {
                focusedNode.children('.project-group-node-header').find('.project-group-node-label').click();
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