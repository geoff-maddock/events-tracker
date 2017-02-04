<?php 
	function delete_form($routeParams, $label = 'Delete')
	{
		$form = Form::open(['method' => 'DELETE', 'route' => $routeParams]);

		$form .= Form::submit($label, ['class' => 'btn btn-danger']);

		return $form .= Form::close();
	}

	function flash($title = null, $message = null)
	{

		$flash = app('App\Http\Flash');

		
		if (func_num_args() == 0) 
		{
			return $flash;	
		};

		return $flash->message($title, $message);
	}

	function link_form($body, $path, $type)
	{
		$csrf = csrf_token();

		if (is_object($path)) {

			$action = '/' . $path->getTable(); // photos
			
			if (in_array($type, ['PUT','PATCH','DELETE'])) {
				$action .= '/' . $path->getKey(); // photos/1
			}
		} else {
			$action = $path;
		}

		return <<< EOT
		<form method="POST" action="{$action}">
			<input type='hidden' name='_method' value='{$type}'>
			<input type="hidden" name="_token" value="{$csrf}">
			<button class="btn btn-danger" type="submit">{$body}</button>
		</form>
EOT;
	}

	function link_form_icon($icon, $path, $type, $title='')
	{
		$csrf = csrf_token();

		if (is_object($path)) {

			$action = '/' . $path->getTable(); // photos
			
			if (in_array($type, ['PUT','PATCH','DELETE'])) {
				$action .= '/' . $path->getKey(); // photos/1
			}
		} else {
			$action = $path;
		}

		return <<< EOT
		<form method="POST" action="{$action}" style="display: inline;">
			<input type='hidden' name='_method' value='{$type}'>
			<input type="hidden" name="_token" value="{$csrf}">
			<button type="submit" class="no-button"><span class="glyphicon {$icon}" title="{$title}"></span></button>
		</form>
EOT;
	}
?>