<?php
namespace App\Http;

use Illuminate\Support\Facades\Session;

class Flash {

	public function message($title, $message)
	{
		\Session::flash('flash_message', [
			'title' => $title,
			'message' => $message,
			'level' => 'info',
			]);
	}

   public function create($title, $message, $level)
   {
   		 \Session::flash('flash_message', [
			'title' => $title,
			'message' => $message,
			'level' => $level,
			]);
   }


	public function error( $title, $message)
	{
		\Session::flash('flash_message', [
			'title' => $title,
			'message' => $message,
			'level' => 'error',
			]);
	}

	public function success( $title, $message)
	{
			\Session::flash('flash_message', [
			'title' => $title,
			'message' => $message,
			'level' => 'success',
			]);
	}
}
?>