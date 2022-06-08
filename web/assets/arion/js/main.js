
/*
 * @package ArionCRM
 * @author Isaac Raway <iraway@metasushi [dot] com>
 * @author Antoinette Smith <asmith@metasushi [dot] com>
 * @link http://arioncrm.com/
 * @copyright (c)2015-2022. MetaSushi, LLC. All rights reserved. Your use of this software in any way indicates agreement
 * to the software license available currenty at http://arioncrm.com/ 
 * This open source edition is released under GPL 3.0. available at https://www.gnu.org/licenses/gpl-3.0.en.html
 */

var main = {
	changes: false, // have any changes been made to the form?
    gridCell: false,
    oldScrollTop: 0,
	setChangesFlag: function()
	{
		main.changes = true;
	},
    textareaEditor: function (container, options) {
        $('<textarea data-bind="value: ' + options.field + '" class="auto-expand" cols="20" rows="4" style="width: 100%;"></textarea>')
            .appendTo(container);
        setTimeout(function() {
            main.refreshExpandableTextAreas();
            // setTimeout(function() {
            //     console.log('current top ', $(".item-detail").scrollTop());
            //     $(main.gridCell).parents(".item-detail").scrollTop(main.oldScrollTop);
            // }, 50);
        }, 50);

    },
    cellEncoding: function(value) {
        return value.replace(new RegExp('\n', 'g'), '<br>\n');
    },
    sumColumn: function(fieldId, column, cost) {
        if(typeof(cost) == 'undefined') cost = 1;
        var ds = $('#'+fieldId).data('kendoGrid').dataSource;
        var aggregates = ds.aggregates();
        console.log('aggregates',aggregates);
        console.log('column',column);
        console.log('cost', cost);
        return '$'+(aggregates[column].sum * cost).format();
    }
}

$(function() {
    var changesConfirmationMessage = 'You have made changes to this item without saving them. You will lose all changes if you leave this page.';

    // $('.k-grid-content tr').on('click', function() {
    //     main.gridCell = this;
    //     console.log('old top ', $(this).parents(".item-detail").scrollTop());
    //     main.oldScrollTop = $(this).parents(".item-detail").scrollTop();
    // });
	/**------ ITEM LIST -------**/	
	// on page load	
	if(document.width < 900) {
		$('#collapsibleListGroup').removeClass('in');
		$('#item-num-message').text('Show items');
	} else {
		$('#item-num-message').text('Hide items');
	}
	
	$('#collapsibleListGroupHeading').on('click', function(event){
		event.preventDefault();
		$('#collapsibleListGroup').collapse('toggle');
	});
	$('#item-num').text($('#collapsibleListGroup').children().children().length);
	$('#collapsibleListGroup').on('hide.bs.collapse', function() {
		$('#item-num-message').text('Show items');
	});
	$('#collapsibleListGroup').on('show.bs.collapse', function() {
		$('#item-num-message').text('Hide items');
	});
    $('#collapsibleListGroup .item-list a').on('click', function(event) {
        event.preventDefault();

        if(!main.changes || confirm(changesConfirmationMessage)) {
            $('.item-list a').removeClass('active');
            $(this).addClass('active');

            // Load just the detail segment from the link
            var url = $(this).attr('href');
            // console.log($(this));
            // console.log($(this).parents('.item-view'));
            // console.log($(this).parents('.item-view').find('.item-detail'));

            //$(this).parents('.item-view').find('.item-detail').load(url + ' .item-detail .inner-panel');
        
            $('.item-detail').html('<div class="item-loading"style=""><p><img src="/assets/arion/images/ajax-loader.gif" /></p><p><i>Loading...</i></p></div>');
            $.ajax({
                url: url,
                type: 'GET',
                success: function(data) {
                    var title = data.match(new RegExp('<title[^>]*>(.*?)</title>'));
                    if(title) {
                        title = title[1].replace('&amp;', '&');
                    } else {
                        title = "";
                    }

                    var $doc = $(data);
                    var content = $doc.find('.item-detail .inner-panel');
                    $('.item-detail').html(content);
                    console.log('loaded item ', title);
                    bindExpandableTextArea();
                    bindCollapseMultiText()
                    bindWindowLinks();
                    main.changes = false;
                    //document.title = document.title.replace('*', '');
                    //window.title = title;
                    window.setWindowTitle(title);
                }
            });
        }
    });

	/**------ FILTER SUBMIT -------**/
	var submitting=false;
	$('#filters').submit(function(event) {
		if(submitting) return;
		submitting=true;
		event.preventDefault();
		$('select').each(function() {
			if($(this).val() == "") $(this).attr('disabled', 'disabled');
		});
		$(this).submit();
	});

	/**------ ITEM DETAIL FORM UNLOAD -------**/
	// add an event listener to each form element
	$('#item-detail .row').each(function() {
		$(this).find('input, select, textarea').on('change', function() {
			//console.log($(this));
			if($(this).attr('name') != 'sendNotifications') {
				main.changes = true;
                if(document.title.indexOf('*') == -1)
                {
                    document.title += '*';
                }
			}
		});
	});

	$('#item-detail').on('submit', function(event) {
		main.changes = false;
        document.title = document.title.replace('*', '');
	});

	// Check for changes when navigating away from page
	$(window).unload( function(e) {
        //console.log($('iframe'));
        $('iframe').each(function(key,element) {
            // console.log('element:',element);
            // console.log('content:');
            // console.log($(element).contents());
            // console.log('title:',$(element).contents().prop('title'));
            if($(element).contents().prop('title').indexOf('*') !== -1) main.changes = true;
        });
        
		if(main.changes){
            // console.log('main.changes true!');
			return changesConfirmationMessage;
		}
	});

	/**------ GRID -------**/
    $('#item-detail').on('submit', function(event) {
        //event.preventDefault();

        // Pack grid data into hidden fields
        $(this).find('.k-grid').each(function(k,element) {
            var fieldId = $(element).attr('id');
            var value = kendo.stringify($(element).data('kendoGrid').dataSource.view());
            $('#'+fieldId+'_value').val(value);
            console.log('value', value);
            console.log('#'+fieldId+'_value', $('#'+fieldId+'_value'));
        });

    });

    // function calculateCost() {
    //     return 1010101;
    // }

    // $(document).ready(function() {
    //     console.log($(this).find('.k-grid'));
    //     $(this).find('.k-grid').each(function(k,element) {
    //         console.log($(element).data('kendoGrid').dataSource);
    //         $(element).data('kendoGrid').dataSource.options.cost = calculateCost();
    //     });
    // });


    /**------ EXPANDABLE TEXT AREA -------**/
    function bindExpandableTextArea() {
        main.refreshExpandableTextAreas = function() {
            $('textarea').each(function() {
                setHeight(this);
            });
        }
        main.refreshExpandableTextAreas();

    	$('.container').on('keyup', '.auto-expand', function() {
    		main.changes = true;
    		setHeight(this);
    	});

    	function setHeight(element)
    	{
    		var minHeight = $(element).data('minheight');
    		if(minHeight === undefined) minHeight = 100;
    		
    		//$(element).parent().height($(element).height()+30+$(element).find('div.error').height());
    		$(element).css({'overflow':'hidden'});
    		$(element).height(0);
    		var height = element.scrollHeight;
    		if(height < minHeight) height = minHeight;
    		$(element).height(height-12);
    		//$(element).parent().height($(element).height()+30+$(element).find('div.error').height());
    	}
    }
    bindExpandableTextArea();

    /**------ MULTITEXT COLLAPSE -------**/
    function bindCollapseMultiText() {
        $('ul.multitext li.collapsed').click(function() {
            $(this).removeClass('collapsed');
            $(this).find('.multi-value').show();
            $(this).find('.summary').hide();
        });
    }
    bindCollapseMultiText();

	/**------ SETTINGS MODAL -------**/
	$('#userSettings').click(function(event) {
		event.preventDefault();
		$('#userSettingsModal').load('/settings/user', function(responseText, textStatus, xhr) {
			function bindSettingsForm() {
				$('#userSettingsModal form').ajaxForm({
					success: function(responseText) {
						$('#userSettingsModal').html(responseText);
						bindSettingsForm();
					}
				});

				$('#passwordRequirements').popover({
    				html : true,
    				width: 200,
    				placement: function() {
    					// console.log(document.width);
    					return document.width > 800 ? 'right' : 'left';
    				},
    				content: function() {
      					return $('#passwordRequirementsContent').html();
    				}
  				});
			}
			bindSettingsForm();
		});
		$('#userSettingsModal').modal();
	});

    /**------ WINDOWS -------**/
    var windows = {};
    
    window.handleWindowLink = function(event,ptitle) {
        if(typeof(event) == 'string')
        {
            var url = event;
            var windowId = undefined;
            var title = ptitle;
        } else {
            var $this = $(this);
            var url = $this.attr('href');
            event.preventDefault();
            var windowId = $this.data('window-id');
            var title = $this.data('window-title');
        }

        if(typeof(windowId) === 'undefined')
        {
            // Make an acceptable ID out of the URL by removing special
            // characters
            windowId = url.replace(new RegExp('[/:&=?.]', 'g'), '');
        }

        if(typeof(title) === 'undefined')
        {
            if($(this).find('h4').length > 0)
            {
                title = $(this).find('h4').text();
            } else {
                title = $(this).text();
            }
        }

        if(typeof(windows[windowId]) !== 'undefined')
        {
            windows[windowId].open();
        } else {
            $div = $('<div />');
            $div.attr('id', 'Window_'+windowId);

            $('.shell').append($div);

            var width = 400;
            if(window.innerWidth > 1500) width = 1400;
            else width = (window.innerWidth-100);

            var windowCount = $('.k-window').length;
            var top = windowCount * 30 + $('.main-menu').height() + 10;
            var left = windowCount * 10 + 10;
            
            windows[windowId] = $div.kendoWindow({
                appendTo: ".shell",
                theme: "material",
                width: width,
                height: "70%",
                title: title,
                content: url,
                iframe: true,
                visible: true,
                position: { top: top, left: left },
                actions: [
                    "Minimize",
                    "Close"
                ],
                deactivate: function() {
                    this.destroy();  
                    windows[windowId] = undefined;                                         
                }
            }).data("kendoWindow");

            var $win = $('#Window_'+windowId);

            // This handles the first page load and sets the kendo window title
            $win.find('iframe').load(function() {
                var title = $(this).contents().find('title').text();
                if(title && title != 'Arion CRM')
                {
                    windows[windowId].title(title);
                }
            });

            // This function is called upon further page loads by the listing code above
            // to set the title as load() is not called in this case.
            updateWindow = function(title) {
                console.log('setWindowTitle !', windows[windowId], title);
                if(title && title != 'Arion CRM')
                {
                    console.log('LOAD!');
                    windows[windowId].title(title);
                    bindWindowLinks();
                    
                    // Rebind this function
                    var iframe = $win.find('iframe')[0];
                    var iframewindow = iframe.contentWindow ? iframe.contentWindow : iframe.contentDocument.defaultView;
                    iframewindow.setWindowTitle = updateWindow;
                }
            }

            var iframe = $win.find('iframe')[0];
            var iframewindow = iframe.contentWindow ? iframe.contentWindow : iframe.contentDocument.defaultView;
            
            iframewindow.setWindowTitle = updateWindow;

        

            $win.find('iframe').load(function() {
                // var $filters = $(this).contents().find('.listing-filters');
                // console.log($filters);
                // console.log($win.parent('.k-window'));
                // $filters.removeClass('row');
                // var $title = $win.parent('.k-window').find('.k-window-title');
                // var $titleBar = $win.parent('.k-window').find('.k-window-titlebar');
                // $titleBar.unbind('click').unbind('mousedown');
                // $title.html('');
                // $title.append($filters);

                // var clickEvents = $._data($filters.find('select'), "events").click;
                // jQuery.each(clickEvents, function(key, handlerObj) {
                //   console.log(handlerObj.handler) // prints "function() { console.log('clicked!') }"
                // })

                windows[windowId].open();
            });

        }
    }

    bindWindowLinks = function() {
        console.log('bindWindowLinks ', window);
        if (window!=window.top) {   // In an iframe
            // Find normal links and replace with Window UI links
            $('.url').click(window.top.handleWindowLink);
            // Tree view listing
            $('.tree-view a.list-group-item').click(window.top.handleWindowLink);
            // File listing
            $('.list-group-item-heading a').click(function(event) {
                event.preventDefault();
                var url = $(this).attr('href');
                window.top.handleWindowLink(url,url);
            });
        } else {                    // In main shell
            // Main menu bar
            $(".main-menu li a").click(window.handleWindowLink);
            // Find normal links and replace with Window UI links
            $('.url').click(window.handleWindowLink);
        }
    }

    window.bindWindowLinks();

    // Find all toolbar spacers and resize them to match the closest toolbar
    $('.toolbars-spacer').each(function() {
        $(this).height($(this).parent().find('.toolbars').height());
    });


    $('form#search').submit(function(event) {
        event.preventDefault();
        var query = $(this).find('input[name=q]').val();
        var url = $(this).attr('action') + '?q=' + query;
        window.handleWindowLink(url, '');
    });
});


(function($, window){
  var arrowWidth = 2;

  $.fn.resizeselect = function(settings) {  
    return this.each(function() { 

      $(this).change(function(){
        var $this = $(this);

        // create test element
        var text = $this.find("option:selected").text();
        var $test = $('<span class="fakeSelect">').html(text.replace('<', '_').replace('>', '_').replace(' ', '_'));

        // add to body, get width, and get out
        $test.appendTo('body');
        var width = $test.width();
        $test.remove();

        // set select width
        $this.width(width + arrowWidth);

        // run on start
      }).change();

    });
  };

  // run by default
  $("select.resizeselect").resizeselect();                       

})(jQuery, window);

$(document).ready(function() {
	$("#filters select").resizeselect();

});


// Production steps of ECMA-262, Edition 5, 15.4.4.18
// Reference: http://es5.github.io/#x15.4.4.18
if (!Array.prototype.forEach) {

  Array.prototype.forEach = function(callback, thisArg) {

    var T, k;

    if (this == null) {
      throw new TypeError(' this is null or not defined');
    }

    // 1. Let O be the result of calling toObject() passing the
    // |this| value as the argument.
    var O = Object(this);

    // 2. Let lenValue be the result of calling the Get() internal
    // method of O with the argument "length".
    // 3. Let len be toUint32(lenValue).
    var len = O.length >>> 0;

    // 4. If isCallable(callback) is false, throw a TypeError exception. 
    // See: http://es5.github.com/#x9.11
    if (typeof callback !== "function") {
      throw new TypeError(callback + ' is not a function');
    }

    // 5. If thisArg was supplied, let T be thisArg; else let
    // T be undefined.
    if (arguments.length > 1) {
      T = thisArg;
    }

    // 6. Let k be 0
    k = 0;

    // 7. Repeat, while k < len
    while (k < len) {

      var kValue;

      // a. Let Pk be ToString(k).
      //    This is implicit for LHS operands of the in operator
      // b. Let kPresent be the result of calling the HasProperty
      //    internal method of O with argument Pk.
      //    This step can be combined with c
      // c. If kPresent is true, then
      if (k in O) {

        // i. Let kValue be the result of calling the Get internal
        // method of O with argument Pk.
        kValue = O[k];

        // ii. Call the Call internal method of callback with T as
        // the this value and argument list containing kValue, k, and O.
        callback.call(T, kValue, k, O);
      }
      // d. Increase k by 1.
      k++;
    }
    // 8. return undefined
  };
}


Number.prototype.format = function(n, x) {
    var re = '\\d(?=(\\d{' + (x || 3) + '})+' + (n > 0 ? '\\.' : '$') + ')';
    return this.toFixed(Math.max(0, ~~n)).replace(new RegExp(re, 'g'), '$&,');
};

