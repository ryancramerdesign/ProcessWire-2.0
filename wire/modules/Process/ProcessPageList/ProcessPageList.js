
/**
 * ProcessWire Page List Process, JQuery Plugin
 *
 * Provides the Javascript/jQuery implementation of the PageList process when used with the JSON renderer
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

$(document).ready(function() {
	if(config.ProcessPageList) $('#' + config.ProcessPageList.containerID).ProcessPageList(config.ProcessPageList); 
}); 

(function($) {

        $.fn.ProcessPageList = function(customOptions) {

		/**
	 	 * List of options that may be passed to the plugin
		 *
		 */
		var options = {
			mode: '',		// 'select' or 'actions', currently this is automatically determined based on the element the PageList is attached to
			limit: 50,		// pagination limit
			rootPageID: 1, 
			selectedPageID: 0, 
			selectStartLabel: 'Change', 
			selectCancelLabel: 'Cancel',
			selectSelectLabel: 'Select',
			showRootPage: true,
			ajaxURL: config.urls.admin + 'page/list/', 	// URL where page lists are loaded from 	
			ajaxMoveURL: config.urls.admin + 'page/sort/' 	// URL where Move's should be saved/posted
		}; 


		$.extend(options, customOptions);

		return this.each(function(index) {

			var $container = $(this); 
			var $root; 
			var $loading = $("<span class='PageListLoading'></span>");
			var firstPagination = 0; // used internally by the getPaginationList() function

			/**
	 		 * Initialize the Page List
			 *
			 */
			function init() {

				$root = $("<div class='PageListRoot'></div>"); 

				if($container.is(":input")) {
					options.selectedPageID = $container.val();
					if(!options.selectedPageID.length) options.selectedPageID = 0;
					options.mode = 'select';
					$container.before($root); 
					setupSelectMode();
				} else {
					options.mode = 'actions'; 
					$container.append($root); 
					loadChildren(options.rootPageID, $root, 0, true); 
				}

			}

			/**
	 		 * Sets up a mode where the user is given a "select" link for each page, rather than a list of actions
			 * 
			 * When they hit "select" the list collapses and the selected page ID is populated into an input
			 *
			 */
			function setupSelectMode() {

				var $actions = $("<ul></ul>").addClass('PageListActions PageListSelectActions actions'); 
				var $pageLabel = $("<p></p>").addClass("PageListSelectName").append($loading); 

				var $action = $("<a></a>").addClass("PageListSelectActionToggle").attr('href', '#').text(options.selectStartLabel).click(function() {
					if($(this).text() == options.selectStartLabel) {
						loadChildren(options.selectedPageID, $root, 0, true); 
						$(this).text(options.selectCancelLabel); 
					} else {
						$root.children(".PageList").slideUp("fast", function() {
							$(this).remove();
						}); 
						$(this).text(options.selectStartLabel); 
					}
					return false; 
				}); 

				$actions.append($("<li></li>").append($action)); 
				$root.append($("<div></div>").addClass('PageListSelectHeader').append($pageLabel).append($actions)); 

				$.getJSON(options.ajaxURL + "?id=" + options.selectedPageID + "&render=JSON&start=0&limit=0", function(data) {
					var label = options.selectedPageID > 0 ? data.page.label : '';
					$root.children(".PageListSelectHeader").find(".PageListSelectName").html(label); 
				}); 
			}

			/**
			 * Method that is triggered when the processChildren() method completes
			 *
			 */
			function loaded() {
			}

			/**
			 * Handles pagination of PageList items
			 *
			 * @param int id ID of the page having children to show
			 * @param start Index that we are starting with in the current list
		 	 * @param int limit The limit being applied to the list (items per page)
			 * @param int total The total number of items in the list (excluding any limits)
			 * @return jQuery $list The pagination list ready for insertion
			 *
			 */
			function getPaginationList(id, start, limit, total) {

				// console.log(start + ", " + limit + ", " + total); 

				var maxPaginationLinks = 9; 
				var numPaginations = Math.ceil(total / limit); 
				var curPagination = start >= limit ? Math.floor(start / limit) : 0;

				if(curPagination == 0) {		
					firstPagination = 0; 
				
				} else if((curPagination-maxPaginationLinks+1) > firstPagination) {
					firstPagination = curPagination - Math.floor(maxPaginationLinks / 2); 

				} else if(firstPagination > 0 && curPagination == firstPagination) {
					firstPagination = curPagination - Math.ceil(maxPaginationLinks / 2); 
				}


				// if we're on the last page of pagination links, then make the firstPagination static at the end
				if(firstPagination > numPaginations - maxPaginationLinks) firstPagination = numPaginations - maxPaginationLinks; 

				if(firstPagination < 0) firstPagination = 0;

				var $list = $("<ul></ul>").addClass("PageListPagination").data('paginationInfo', {
					start: start,
					limit: limit,
					total: total
				}); 

				/**
				 * paginationClick is the event function called when an item in the pagination nav is clicked
				 *
				 * It loads the new pages (via loadChildren) and then replaces the old pageList with the new one
				 *
				 */
				var paginationClick = function(e) {
					var $curList = $(this).parents("ul.PageListPagination");
					var info = $curList.data('paginationInfo'); 
					var $newList = getPaginationList(id, parseInt($(this).attr('href')) * info.limit, info.limit, info.total);
					var $loading = $("<li class='PageListLoading'></li>"); 
					$curList.siblings(".PageList").remove(); // remove any open lists below current
					$curList.replaceWith($newList); 
					$newList.append($loading); 
					var $siblings = $newList.siblings().css('opacity', 0.5);
					loadChildren(id, $newList.parent(), $(this).attr('href') * info.limit, false, false, true, function() {
						$loading.remove();
					}); 
					return false;	
				}
		
				var $separator = null;
				var $blankItem = null;
	
				for(var pagination = firstPagination, cnt = 0; pagination < numPaginations; pagination++, cnt++) {

					var $a = $("<a></a>").html(pagination+1).attr('href', pagination)
					var $item = $("<li></li>").append($a).addClass('ui-state-default');

					if(pagination == curPagination) {
						$item.addClass("PageListPaginationCurrent ui-state-focus"); 
					}

					$list.append($item); 

					if(!$blankItem) $blankItem = $item.clone().removeClass('PageListPaginationCurrent ui-state-focus'); 
					if(!$separator) $separator = $blankItem.clone().removeClass('ui-state-default').html("&hellip;"); 

					if(cnt >= maxPaginationLinks && pagination < numPaginations) {
						$lastItem = $blankItem.clone();
						$lastItem.find("a").text(numPaginations).attr('href', numPaginations-1);
						$list.append($separator.clone()).append($lastItem); 
						break;
					} 
				}


				if(firstPagination > 0) {
					$firstItem = $blankItem.clone();
					$firstItem.find("a").text("1").attr('href', '0').click(paginationClick); 
					$list.prepend($separator.clone()).prepend($firstItem); 
				}

				//if(curPagination+1 < maxPaginationLinks && curPagination+1 < numPaginations) {
				if(curPagination+1 < numPaginations) {
					$nextBtn = $blankItem.clone();
					$nextBtn.find("a").html("&gt;").attr('href', curPagination+1); 
					$list.append($nextBtn);
				}

				if(curPagination > 0) {
					$prevBtn = $blankItem.clone();
					$prevBtn.find("a").attr('href', curPagination-1).html("&lt;"); 
					$list.prepend($prevBtn); 
				}

				$list.find("a").click(paginationClick)
					.hover(function() { $(this).addClass('ui-state-hover'); }, function() { $(this).removeClass("ui-state-hover"); }); 


				return $list;
			}

			/**
	 		 * Load children via ajax call, attach them to $target and show. 
			 *
			 * @param int id ID of the page having children to show
			 * @param jQuery $target Item to attach children to
		 	 * @param int start If not starting from first item, num of item to start with
			 * @param bool beginList Set to true if this is the first call to create the list
			 * @param bool replace Should any existing list be replaced (true) or appended (false)
			 * @param bool pagination Set to false if you don't want pagination, otherwise leave it out
			 *
			 */
			function loadChildren(id, $target, start, beginList, pagination, replace, callback) {

				if(pagination == undefined) pagination = true; 
				if(replace == undefined) replace = false;

				var processChildren = function(data) {

					if(data.error) {
						alert(data.message); 
						$loading.hide();
						return; 
					}

					var $children = listChildren($(data.children)); 
					var nextStart = data.start + data.limit; 

					if(data.page.numChildren > nextStart) {
						var $a = $("<a></a>").attr('href', nextStart).data('pageId', id).text('More').click(clickMore); 
						$children.append($("<ul></ul>").addClass('PageListActions actions').append($("<li></li>").addClass('PageListActionMore').append($a)));
						if(pagination) $children.prepend(getPaginationList(id, data.start, data.limit, data.page.numChildren));
					}

					$children.hide();

					if(beginList) {
						var $listRoot; 
						$listRoot = listChildren($(data.page)); 
						if(options.showRootPage) $listRoot.children(".PageListItem").addClass("PageListItemOpen"); 
							else $listRoot.children('.PageListItem').hide().parent('.PageList').addClass('PageListRootHidden'); 
						$listRoot.append($children); 
						$target.append($listRoot);

					} else if($target.is(".PageList")) {
					
						var $newChildren = $children.children(".PageListItem, .PageListActions"); 
						if(replace) $target.children(".PageListItem, .PageListActions").replaceWith($newChildren); 
							else $target.append($newChildren); 

					} else {
						$target.after($children); 
					}

					$loading.hide();

					if(replace) {
						$children.show();
						loaded();
						if(callback != undefined) callback();
					} else { 
						$children.slideDown("fast", function() {
							loaded();
							if(callback != undefined) callback();
						}); 
					}
				}; 

				if(!replace) $target.append($loading.show()); 
				$.getJSON(options.ajaxURL + "?id=" + id + "&render=JSON&start=" + start, processChildren); 
			}

			/**
			 * Given a list of pages, generates a list of them
			 *
			 * @param jQuery $children
			 *
			 */ 
			function listChildren($children) {

				var $list = $("<div></div>").addClass("PageList");
				var $ul = $list;

				$children.each(function(n, child) {
					$ul.append(listChild(child)); 
				}); 	

				$("a.PageListPage", $ul).click(clickChild); 
				$(".PageListActionMove a", $ul).click(clickMove); 
				$(".PageListActionSelect a", $ul).click(clickSelect); 

				return $list; 
			}

			/**
			 * Given a single page, generates the list item for it
			 *
			 * @param map child
			 *
			 */
			function listChild(child) {

				var $li = $("<div></div>").data('pageId', child.id).addClass('PageListItem'); 
				var $a = $("<a></a>")
					.attr('href', '#')
					.attr('title', child.path)
					.text(child.label)
					.addClass('PageListPage label'); 

				if(child.status == 0) $li.addClass('PageListStatusOff disabled');
				if(child.status & 1024) $li.addClass('PageListStatusHidden secondary'); 
				if(child.status & 4) $li.addClass('PageListStatusLocked'); 

				$li.append($a); 
				var $numChildren = $("<span>" + (child.numChildren ? child.numChildren : '') + "</span>").addClass('PageListNumChildren detail'); 
				$li.append($numChildren); 
		
				if(child.note.length) $li.append($("<span>" + child.note + "</span>").addClass('PageListNote detail')); 	
				
				var $actions = $("<ul></ul>").addClass('PageListActions actions'); 
				var links = [{ name: options.selectSelectLabel, url: '#'}]; 
				if(options.mode == 'actions') links = child.actions; 

				$(links).each(function(n, action) {
					var $a = $("<a></a>").text(action.name)
						.attr('href', action.url); 
					$actions.append($("<li></li>").addClass('PageListAction' + action.name).append($a)); 
				}); 

				$li.append($actions); 
				return $li;
			}

			/**
			 * Event called when a page label is clicked on
			 *
			 * @param event e
			 *
			 */
			function clickChild(e) {

				var $t = $(this); 
				var $li = $t.parent('.PageListItem'); 
				var id = $li.data('pageId');

				if($root.is(".PageListSorting")) return false; 

				if($li.is(".PageListItemOpen")) {
					$li.removeClass("PageListItemOpen").next(".PageList").slideUp("fast", function() { $(this).remove(); }); 
				} else {
					$li.addClass("PageListItemOpen"); 
					if(parseInt($li.children('.PageListNumChildren').text()) > 0) {
						loadChildren(id, $li, 0, false); 
					}
				}
					
				return false;
			}

			/**
			 * Event called when the 'more' action/link is clicked on
			 *
			 * @param event e
			 *
			 */
			function clickMore(e) {

				var $t = $(this); 
				var $actions = $t.parent('li').parent('ul.PageListActions'); 
				var $pageList = $actions.parent('.PageList'); 
				var id = $t.data('pageId');
				var nextStart = parseInt($t.attr('href')); 
		
				loadChildren(id, $pageList, nextStart, false); 
				$actions.remove();
				return false; 
			}

			/**
			 * Event called when the 'move' action/link is clicked on
			 *
			 * @param event e
			 *
			 */
			function clickMove() {

				var $t = $(this); 
				var $li = $t.parent('li').parent('ul.PageListActions').parent('.PageListItem'); 

				$li.children(".PageListPage").click(); 

				// make an invisible PageList placeholder that allows 'move' action to create a child below this
				$root.find('.PageListItemOpen').each(function() {
					var numChildren = $(this).children('.PageListNumChildren').text(); 
					// if there are children and the next sibling doesn't contain a visible .PageList, then don't add a placeholder
					if(parseInt(numChildren) > 1 && $(this).next().find(".PageList:visible").size() == 0) {
						return; 
					}
					var $ul = $("<div></div>").addClass('PageListPlaceholder').addClass('PageList');
					$ul.append($("<div></div>").addClass('PageListItem PageListPlaceholderItem').html('&nbsp;'));
					$(this).after($ul);
					//$(this).prepend($ul.clone()); 
					//$(this).addClass('PageListItemNoSort'); 
				}); 

				var options = {
					stop: stopMove, 
					helper: 'PageListItemHelper', 
					items: '.PageListItem:not(.PageListItemOpen)',
					placeholder: 'PageListSortPlaceholder',
					start: function(e, ui) {
						$(".PageListSortPlaceholder").css('width', ui.item.children(".PageListPage").outerWidth() + 'px'); 
					}
				};

				var $sortRoot = $root.children('.PageList').children('.PageList');

				var $cancelLink = $("<a href='#'>Cancel</a>").click(function() { 
					return cancelMove($li); 
				}); 

				$li.children("ul.PageListActions").before($("<span class='PageListMoveNote detail'>&lt; Click and Drag to Move or </span>").append($cancelLink)); 
				$li.addClass('PageListSortItem'); 
				$li.parent('.PageList').attr('id', 'PageListMoveFrom'); 

				$root.addClass('PageListSorting'); 
				$sortRoot.addClass('PageListSortingList').sortable(options); 

				return false; 

			}

			/**
			 * Remove everything setup from an active 'move' 
			 *
			 * @param jQuery $li List item that initiated the 'move'
			 *
			 */
			function cancelMove($li) {
				var $sortRoot = $root.find('.PageListSortingList'); 
				$sortRoot.sortable('destroy').removeClass('PageListSortingList'); 
				$li.removeClass('PageListSortItem').parent('.PageList').removeAttr('id'); 
				$li.find('.PageListMoveNote').remove();
				$root.find(".PageListPlaceholder").remove();
				$root.removeClass('PageListSorting'); 
				return false; 
			}

			/**
			 * Event called when the mouse stops after performing a 'move'
			 *
			 * @param event e
			 * @param jQueryUI ui
			 *
			 */
			function stopMove(e, ui) {

				var $li = ui.item; 
				var $a = $li.children('.PageListPage'); 
				var id = parseInt($li.data('pageId')); 
				var $ul = $li.parent('.PageList'); 

				// get the previous sibling .PageListItem, and skip over the pagination list if it's there
				var $ulPrev = $ul.prev().is('.PageListItem') ? $ul.prev() : $ul.prev().prev();
				var parent_id = parseInt($ulPrev.data('pageId')); 

				// check if item was moved to an invalid spot
				// in this case, a spot between another open PageListItem and it's PageList
				var $liPrev = $li.prev(".PageListItem"); 
				if($liPrev.is(".PageListItemOpen")) return false; 

				// check if item was moved into an invisible parent placeholder PageList
				if($ul.is('.PageListPlaceholder')) {
					// if so, it's no longer a placeholder, but a real PageList
					$ul.removeClass('PageListPlaceholder').children('.PageListPlaceholderItem').remove();
				}

				cancelMove($li); 

				// setup to save the change
				$li.append($loading.show()); 
				var sortCSV = '';
			
				// create a CSV string containing the order of Page IDs	
				$ul.children(".PageListItem").each(function() {
					sortCSV += $(this).data('pageId') + ','; 
				}); 

				var postData = {
					id: id, 
					parent_id: parent_id, 
					sort: sortCSV
				}; 

				var success = 'unknown'; 
			
				// save the change	
				$.post(options.ajaxMoveURL, postData, function(data) {

					$loading.hide();

					$a.fadeOut('fast', function() {
						$(this).fadeIn("fast")
						$li.removeClass('PageListSortItem'); 
						$root.removeClass('PageListSorting');
						//$a.click();
					}); 

					if(data.error) {
						alert(data.message); 
					}

					// if item moved from one list to another, then update the numChildren counts
					$from = $("#PageListMoveFrom").attr('id', ''); 
					if(!$ul.is("#PageListMoveFrom")) {
						// update count where item came from
						var $fromItem = $from.prev(".PageListItem"); 	
						var $numChildren = $fromItem.children(".PageListNumChildren"); 
						var n = $numChildren.text().length > 0 ? parseInt($numChildren.text()) - 1 : 0; 
						if(n == 0) {
							n = '';
							$from.remove(); // empty list, no longer needed
						}
						$numChildren.text(n); 
				
						// update count where item went to	
						var $toItem = $ul.prev(".PageListItem"); 
						$numChildren = $toItem.children(".PageListNumChildren"); 	
						n = $numChildren.text().length > 0 ? parseInt($numChildren.text()) + 1 : 1; 
						$numChildren.text(n); 
					}
				}, 'json'); 

				return true; // whether or not to allow the sort
			}

			/**
			 * Event called when the "select" link is clicked on in select mode
			 *
			 * @see setupSelectMode()
			 *
			 */
			function clickSelect() {

				var $t = $(this); 
				var $li = $t.parent('li').parent('ul.PageListActions').parent('.PageListItem'); 
				var id = $li.data('pageId');
				var $header = $root.children(".PageListSelectHeader"); 
				var $a = $li.children(".PageListPage"); 
				var title = $a.text();
				var url = $a.attr('title'); 

				if(id != $container.val()) $container.change().val(id);
				$header.children(".PageListSelectName").text(title); 
				$header.find(".PageListSelectActionToggle").click();
					
				$container.trigger('pageSelected', { id: id, url: url, title: title }); 	

				return false; 
			}

			// initialize the plugin
			init(); 
		}); 
	};
})(jQuery); 
