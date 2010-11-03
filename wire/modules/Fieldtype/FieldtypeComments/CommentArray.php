<?php

/**
 * ProcessWire FieldtypeComments > CommentArray
 *
 * Maintains an array of multiple Comment instances.
 * Serves as the value referenced when a FieldtypeComment field is reference from a Page.
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

class CommentArray extends WireArray {

	/**
	 * Per the WireArray interface, is the item a Comment
	 *
	 */
	public function isValidItem($item) {
		return $item instanceof Comment; 	
	}

	/**
	 * Provides the default rendering of a comment list, which may or may not be what you want
 	 *
	 * @see CommentList class and override it to serve your needs
	 *
	 */
	public function render($options = array()) {
		$commentList = $this->getCommentList($options); 
		return $commentList->render();
	}

	/**
	 * Provides the default rendering of a comment form, which may or may not be what you want
 	 *
	 * @see CommentForm class and override it to serve your needs
	 *
	 */
	public function renderForm(Page $page, $options = array()) {
		$form = $this->getCommentForm($page, $options); 
		return $form->render();
	}

	/**
	 * Return instance of CommentList object
	 *
	 */
	public function getCommentList($options = array()) {
		return new CommentList($this, $options); 	
	}

	/**
	 * Return instance of CommentForm object
	 *
	 */
	public function getCommentForm(Page $page, $options = array()) {
		return new CommentForm($page, $this, $options); 
	}
}


