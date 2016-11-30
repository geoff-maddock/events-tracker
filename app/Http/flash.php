<?php
namespace App\Http;

class Flash {


	public function message($title, $message)
	{

		session()->flash('flash_message', [
			'title' => $title,
			'message' => $message,
			'level' => 'info',
			]);
				//dd('calling msg11');
	}

   public function create($title, $message, $level)
   {
   	return session()->flash('flash_message', [
			'title' => $title,
			'message' => $message,
			'level' => $level,
			]);
   }



	public function error($title, $message)
	{
		session()->flash('flash_message', [
			'title' => $title,
			'message' => $message,
			'level' => 'error',
			]);
	}

	public function success($title, $message)
	{
		session()->flash('flash_message', [
			'title' => $title,
			'message' => $message,
			'level' => 'success',
			]);
	}
}
?>