/**
  * simple fileupload
  * 
  * thanks to https://github.com/pangratz/dnd-file-upload/blob/master/jquery.dnd-file-upload.js
  *@package goma
  *@link http://goma-cms.org
  *@license: http://www.gnu.org/licenses/gpl-3.0.html see "license.txt"
  *@Copyright (C) 2009 - 2012  Goma-Team
  * last modified: 06.03.2012
  * $Version 1.1
*/


function FileUpload(formelement, url) {
	var $this = this;
	this.formelement = $(formelement);
	this.element = $(formelement).find(".icon").get(0);
	this.destInput = $(formelement).find(".FileUploadValue");
	
	// the info-zone
	this.formelement.find(".actions").append('<div class="progress_info"></div>');
	this.infoZone = this.formelement.find(".actions").find(".progress_info");
	
	// append fallback for not drag'n'drop-browsers
	this.formelement.find(".actions").append('<input type="button" class="button fileSelect" value="'+lang("files.browse")+'" />');
	this.browse = this.formelement.find(".actions").find(".fileSelect");
	
	this.uploader = new AjaxUpload("#" + this.element.id, {
		url: url + "/frameUpload/",
		ajaxurl: url + "/ajaxUpload/",
		browse: this.browse,
		
		// events
		uploadStarted: function() {
			var that = this;
			$this.infoZone.html('<div class="progressbar"><div class="progress"></div><span><img src="images/16x16/loading.gif" alt="Uploading.." /></span><div class="cancel"></div></div>');
			$this.infoZone.find(".cancel").click(function(){
				that.abort();
			});
			$($this.element).append('<div class="loading"></div>');
		},
		dragInDocument: function() {
			$($this.element).addClass("active");
		},
		dragLeaveDocument: function() {
			$($this.element).removeClass("active");
			$($this.element).removeClass("beforeDrop");
		},
		
		dragOver: function() {
			$($this.element).addClass("active");
			$($this.element).addClass("beforeDrop");
		},
		
		/**
		 * called when the speed was updated, just for ajax-upload
		*/
		speedUpdate: function(fileIndex, file, KBperSecond) {
			var ext = "KB/s";
			KBperSecond = Math.round(KBperSecond);
			if(KBperSecond > 1000) {
				KBperSecond = Math.round(KBperSecond / 1000, 2);
				ext = "MB/s";
			}
			$this.infoZone.find("span").html(KBperSecond + ext);
		},
		
		/**
		 * called when the progress was updated, just for ajax-upload
		*/
		progressUpdate: function(fileIndex, file, newProgress) {
			$this.infoZone.find(".progress").stop().animate({width: newProgress + "%"}, 500);
		},
		
		/**
		 * event is called when the upload is done
		*/
		always: function() {
			$this.infoZone.find("span").html("100%");
			$this.infoZone.find(".progress").css("width", "100%");
			setTimeout(function(){
				$this.infoZone.find(".progressbar").slideUp("fast", function(){
					$this.infoZone.html("");
				});
			}, 1000);
			
			$($this.element).find(".loading").remove();
		},
		
		/**
		 * method which is called, when we receive the response
		*/
		done: function(html) {
			try {
				var data = eval('('+html+');');
				if(data.status == 0) {
					$this.infoZone.html('<div class="error">'+data.errstring+'</div>');
				} else {
					$($this.element).find("img").attr("src", data.file.icon);
					if(data.file.path)
						$($this.element).find("a").attr("href", data.file.path);
					else
						$($this.element).find("a").removeAttr("href");
					$($this.element).find("span").html(data.file.name);
					$this.destInput.val(data.file.realpath);
				}
			} catch(err) {
				if(this.isAbort) {
				
				} else {
					$this.infoZone.html('<div class="error">An Error occured.</div>');
				}
			}
		},
		
	});
	
	// now hide original file-upload-field
	this.formelement.find(".no-js-fallback").css("display", "none");
	
	return this;
}
