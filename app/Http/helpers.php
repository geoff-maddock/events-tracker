<?php

use App\Http\Flash;

function delete_form(array $routeParams, string $label = 'Delete'): string
{
    $form = Form::open(['method' => 'DELETE', 'route' => $routeParams, 'id' => 'deleteForm', 'style' => 'display: inline;']);

    $form .= Form::submit($label, ['class' => 'btn btn-danger delete confirm']);

    $form .= Form::close();

    return $form;
}

function getTableFromModelClass(string $class): string
{
    // convert entity class into table
    $split = explode('\\', $class);

    return isset($split[2]) ? $split[2] : $class;
}

function flash(?string $title = null, ?string $message = null): ?Flash
{
    $flash = app('App\Http\Flash');

    if (0 == func_num_args()) {
        return $flash;
    }

    $flash->message($title, $message);

    return null;
}

function link_form(string $body, mixed $path, string $type): string
{
    $csrf = csrf_token();

    if (is_object($path)) {
        $action = '/'.$path->getTable(); // photos

        if (in_array($type, ['PUT', 'PATCH', 'DELETE'])) {
            $action .= '/'.$path->getKey(); // photos/1
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

function link_form_icon(
    string $icon,
    mixed $path,
    string $type,
    ?string $title = '',
    ?string $label = '',
    ?string $class = '',
    ?string $confirm = 'confirm'
    ): string {
    $csrf = csrf_token();
    $object = 'object';

    if (is_object($path)) {
        $object = get_class_name(get_class($path));

        $action = '/'.str_replace('_', '-', $path->getTable());

        if (in_array($type, ['PUT', 'PATCH', 'DELETE'])) {
            $action .= '/'.$path->getKey();
        }
    } else {
        $action = $path;
    }

    return <<< EOT
    <form method="POST" action="{$action}" style="display: inline;">
        <input type='hidden' name='_method' value='{$type}'>
        <input type="hidden" name="_token" value="{$csrf}">
        <button type="submit" class="{$confirm} no-button {$class}" data-type="{$object}">{$label} <span class="glyphicon {$icon}" title="{$title}"></span></button>
    </form>
EOT;
}

function link_form_bootstrap_icon(string $icon, mixed $path, string $type, ?string $title = '', ?string $label = '', ?string $class = '', ?string $confirm = 'confirm'): string
{
    $csrf = csrf_token();
    $object = 'object';

    if (is_object($path)) {
        $object = get_class_name(get_class($path));

        $action = '/'.str_replace('_', '-', $path->getTable());

        if (in_array($type, ['PUT', 'PATCH', 'DELETE'])) {
            $action .= '/'.$path->getKey();
        }
    } else {
        $action = $path;
    }

    return <<< EOT
    <form method="POST" action="{$action}" style="display: inline;">
        <input type='hidden' name='_method' value='{$type}'>
        <input type="hidden" name="_token" value="{$csrf}">
        <button type="submit" class="{$confirm} no-button {$class}" data-type="{$object}">{$label} <i class="{$icon}" title="{$title}"></i></button>
    </form>
EOT;
}

function get_class_name(string $classname): string
{
    if ($pos = strrpos($classname, '\\')) {
        return substr($classname, $pos + 1);
    }

    return '';
}

function split_name(string $name): array
{
    $name = trim($name);
    if (false === strpos($name, ' ')) {
        // you can return the firstname with no last name
        return ['firstname' => $name, 'lastname' => ''];
    }

    $parts = explode(' ', $name);
    $lastname = array_pop($parts);
    $firstname = implode(' ', $parts);

    return ['firstname' => $firstname, 'lastname' => $lastname];
}
