<?php

class ErrorController extends Controller{

	public function indexAction(){
		header("HTTP/1.0 404 Not Found");
	}

}