// The CodeManager object. This manages how the code is displayed,
// interacted with, and commented on.
var CodeManager = {};

CodeManager.is_interactive = true;
CodeManager.current_file = null;
CodeManager.dragging_in_file = null;
CodeManager.is_code_page = false; // overridden on code page

CodeManager.Shortcuts = {
    
    
    all: {
        // Tab lets you submit a comment
        "tab" : function(){
            var cur_file = CodeManager.current_file;
            var comment = cur_file.getCurrentComment();
            if(comment){
                comment.submit();
            }
        },

        // Show the keyboard shortucts available to this user on ctrl+1
        "ctrl+1" : function(){
             if($("#shortcuts").html() != null){
			     $("#shortcuts").remove();
			 }else{
				 var display = shortcutsBase;
				 display += shortcutsAll;
                 if(CodeManager.is_interactive){
				     display += shortcutsEdit;
                 }
			 }
			 $("body").append(display);
        },

        // Display a markdown quick reference on ctrl+3
        "ctrl+3" : function(){
            if(!CodeManager.is_interactive) return;
            if($("#shortcuts").html() != null){
        		 $("#shortcuts").remove();
        	}else{
        	    var display = markdownBase + markdownRef;
        		$("body").append(display);
        	}            
        },
    },
    
    // Set up all of the shortcuts
    setup: function(){
        if(!CodeManager.is_code_page) return;
        for(var command in CodeManager.Shortcuts.all){
            var fn = CodeManager.Shortcuts.all[command];
            shortcut.add(command, new SafeFunction(fn));
        }
    }
}

/*
 * The DisplayController manages the aspects relating to the display of code files.
 * We can hide all the files from the display, or display an individual file. We also
 * provide functionality to alter display settings on the comments, and also to show
 * all of the files.
 *
 * @author  Jeremy Keeshin  December 26, 2011
 */
CodeManager.DisplayController = {
    
    /*
     * This method registers a function to be called on clicking an element. We pass in the selector
     * and the callback method. The callback is called when this element is clicked, and we pass
     * the element as a parameter which can be used optionally.
     *
     * @param   selector    {string}    the jQuery selector string for the element
     * @param   callback    {fn}        the callback function
     * @author  Jeremy Keeshin  December 28, 2011
     */
    register_click_function: function(selector, callback){
        $(selector).click(function(e){
            e.preventDefault();
            callback(this);
        });
    },
        
    /*
     * This sets up the display controller. We give the user options to determine
     * visibility settings on all of the code, comments, or all of the files. We
     * dispatch off to the correct method on clicks to certain buttons.
     */
    setup: function(){
        if($('#code_options_box').length == 0) return;
        
        CodeManager.DisplayController.register_click_function('#option_hide_comments', CodeManager.DisplayController.hide_all_comments);
        CodeManager.DisplayController.register_click_function('#option_show_comments', CodeManager.DisplayController.show_all_comments);
        CodeManager.DisplayController.register_click_function('#option_all_files', CodeManager.DisplayController.show_all_files);
        CodeManager.DisplayController.register_click_function('#option_hide_code', CodeManager.DisplayController.hide_code_lines);
        CodeManager.DisplayController.register_click_function('#option_show_code', CodeManager.DisplayController.show_code_lines);
        CodeManager.DisplayController.register_click_function('#option_toggle_view_only', CodeManager.DisplayController.toggle_interactivity);
        CodeManager.DisplayController.register_click_function('#option_full_tree', CodeManager.DisplayController.toggle_full_tree);

        
        
        CodeManager.DisplayController.monitor_options_bar();
    },
    
    /*
     * This function monitors the top options bar. This means it starts in a relative position
     * in line with the code, but if we scroll down, we fix it at the top of the page
     * to keep it visible. However, if we scroll back up again, we unfix it and put it into place
     *
     * @author  Jeremy Keeshin  December 28, 2011
     */
    monitor_options_bar: function(){
        var fixed = false;
        var offset = $('#code_options_box').offset().top;

        $(document).scroll(function(){
            var scroll_pos = $(document).scrollTop();
            
            // We have scrolled down. Fix the position.
            if(offset <= scroll_pos){
                fixed = true;
                $('#code_options_box').css({
                    'position': 'fixed',
                    'top': '0px',
                    'width': '893px'
                });
            
            // We were already fixed, and we have scrolled back up. Reset.
            }else if(fixed){                 
                $('#code_options_box').css({
                    'position': 'relative',
                    'top': '',
                    'width': ''
                });
                fixed = false;
            }
        });
    },
    
    // Toggle whether the code can be commented on or not
    toggle_interactivity: function(elem){
        if(CodeManager.is_interactive){
            $(elem).html('Make interactive');
        }else{
            $(elem).html('Read only');
        } 
        CodeManager.is_interactive= !CodeManager.is_interactive;
    },
    
    // Hide all of the lines of code
    hide_code_lines: function(){
        $('.line').hide();
    },
    
    // Show all of the lines of code
    show_code_lines: function(){
        $('.line').show();
    },
    
    /* Hide all of the code files */
    hide_all_files: function(){
        $('.code_container').hide();
        $('.selectedFile').removeClass('selectedFile');
    },
    
    /* 
     * Display an individual file with the given name
     * @param   file    {string}    the name of the file
     */
    display_file: function(id){
        CodeManager.DisplayController.hide_all_files();
        $('.code_container[data-id="'+id+'"]').show();
        $('.filelink[data-id="'+id+'"]').addClass('selectedFile');
        
        // Set as current file
        if(CodeManager.code_files){
            CodeManager.current_file = CodeManager.code_files[id];
        }
    },
    
    /* Show all the files in this assignment */
    show_all_files: function(){
        $('.code_container').show();
        $('.selectedFile').removeClass('selectedFile');
    },
    
    /* Hide all of the comments */
    hide_all_comments: function(){
        $('.inlineComment').hide();
    },
    
    /* Show all of the comments */
    show_all_comments: function(){
        $('.inlineComment').show();
    },
    
    /* Display the full tree of files */
    toggle_full_tree: function(){
        $('#fulltree').toggle();
    }
}

/*
 * Helper to get a property value from a data-* attribute.
 * @param   prop {string}       the name of the property
 */
CodeManager.get_property = function(elem, prop){
    return $(elem).attr(prop);
}

/* 
 * Return the name of a file based on its data-name attribute
 * @param   elem    {elem}      the DOM element
 */
CodeManager.get_name = function(elem){
    return CodeManager.get_property(elem, 'data-name');
}

CodeManager.get_id = function(elem){
    return CodeManager.get_property(elem, 'data-id');
}



/*
 * Setup the functionality when a user clicks the name of the 
 * file, then we should show that file. We get all of the file links
 * and get their name, and each time they are clicked, display that
 * file and hide the others.
 */
CodeManager.setup_file_selection = function(){
    $('.filelink a').each(function(idx, elem){
        var id = CodeManager.get_id(elem);
        $(this).click(function(e){
            e.preventDefault();
            CodeManager.DisplayController.display_file(id);
        });
    });
    CodeManager.DisplayController.display_file(0); // Display the first file.
}

CodeManager.setup_code_files = function(){
    CodeManager.code_files = [];
    D.log('setting up code files...');
    $('.code_container').each(function(idx, elem){
        var code_file = new CodeFile({
            filename: CodeManager.get_name(elem),
            id_number: CodeManager.get_id(elem),
            interactive: CodeManager.is_interactive,
            user: CodeManager.commenting_user
        });
        code_file.setupComments();
        code_file.showComments();
        CodeManager.code_files.push(code_file);
    });
    CodeManager.bind_editing();
    
    CodeManager.current_file = CodeManager.code_files[0]; // set the first file
}

CodeManager.bind_editing = function(){
    if(!CodeManager.is_interactive) {
        D.log('not interactive');
        return;
    }
    
    $('.inlineComment').unbind();
    $('.inlineComment').click(function(){
        if(!CodeManager.is_interactive){
            return;
        }
        var comment_id = $(this).attr('data-id');
        var file_id = $(this).attr('data-file');        
        var file = CodeManager.code_files[file_id];
        var comment = file.get_comment_by_db_id(comment_id);
        if(comment){
            comment.edit();
        }
    })
}

CodeManager.Releaser = {
    // Setup the releaser. We listen to changes on the release checkbox.
    setup: function(){
        $(':checkbox').change(function() {
    		CodeManager.Releaser.release(this);
    	});
    },
    
    // Handles releasing the code file
    release: function(elem){
    	var action = "delete";
    	var val = $('input:checkbox:checked').val(); 
    	if(val != undefined){
    	 	action = "create";
    	}
    	
    	var student = $(elem).attr('student');
    	
    	$.ajax({
    	   	type: 'POST',
    	   	url: window.location.pathname, // post to current location url
    	   	data: {
    	   	    action: 'release',
    	   	    release: action,
    	   	    student: student
    	   	},
    	   	dataType: 'JSON',
    	   	success: function(response) {
        		if(response && response.status == "ok"){
        			CodeManager.Releaser.transitions.showSaved();
        			$('#'+student).toggleClass('bold');
        		}else{
        			alert("Bad message sent in releasing the comments.");
        		}
    	   	},
    	   	error: function(x, textStatus, errorThrown) {
    		    alert("There was an error in releasing the comments.");
       	    }
    	});
    },
    
    // Handle releaser transitions
    transitions: {
        // Resets save message to hidden.
        resetSaved: function(){
        		$("#saved").addClass("hidden");
        },
        
        // Fades out the message
        fade: function(){	
        		$("#saved").fadeOut(800);
        		setTimeout(CodeManager.Releaser.transitions.resetSaved, 700);
        },
        
        // Show the saved message.
        showSaved: function(){
        		$("#saved").removeClass("hidden");
        		$("#saved").css("display", "block");
        		setTimeout(CodeManager.Releaser.transitions.fade, 700);
        }
    }
};

$(document).bind("status.finishedSyntaxHighlighting", CodeManager.setup_code_files);

$(function(){
   CodeManager.setup_file_selection(); 
   CodeManager.Releaser.setup();
   CodeManager.DisplayController.setup();
   CodeManager.Shortcuts.setup();
   D.log(CodeManager);
});