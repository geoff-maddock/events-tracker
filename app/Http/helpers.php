<?php

use App\Http\Flash;

function delete_form(array $routeParams, string $label = 'Delete'): string
{
    $name = array_shift($routeParams);
    $action = route($name, $routeParams);

    // Native equivalent of the old Form::open(DELETE) helper: keeps the
    // id="deleteForm" and the submit's "delete confirm" class that the
    // SweetAlert confirmation JS hooks onto, plus method spoofing + CSRF.
    return '<form method="POST" action="' . e($action) . '" accept-charset="UTF-8" id="deleteForm" style="display: inline;">'
        . '<input name="_method" type="hidden" value="DELETE">'
        . '<input name="_token" type="hidden" value="' . e(csrf_token()) . '">'
        . '<input class="btn btn-danger delete confirm" type="submit" value="' . e($label) . '">'
        . '</form>';
}

/**
 * Native replacement for laravelcollective/html's link_to_route() global.
 * Renders <a href="{route}" ...attributes>{title}</a> with the title and
 * attribute values HTML-escaped (matching the package default).
 *
 * Guarded with function_exists() so this autoloaded file never triggers a
 * "Cannot redeclare function" fatal if the laravelcollective package is still
 * present in vendor/ during a deploy (i.e. before composer install removes it).
 * The package's identical helper is used until then; ours takes over after.
 *
 * @param array<int|string, mixed> $parameters
 * @param array<string, mixed>     $attributes
 */
if (!function_exists('link_to_route')) {
    function link_to_route(string $name, ?string $title = null, array $parameters = [], array $attributes = []): string
    {
        $url = route($name, $parameters);

        $attrs = '';
        foreach ($attributes as $key => $value) {
            $attrs .= is_int($key)
                ? ' ' . e($value)
                : ' ' . $key . '="' . e($value) . '"';
        }

        return '<a href="' . e($url) . '"' . $attrs . '>' . e($title ?? $url) . '</a>';
    }
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
