<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
$route['default_controller'] = 'welcome';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

/*
	Routes
*/
$route['routes']['get'] = 'routes/getRoutes';
$route['routes']['post'] = 'routes/addRoute';

//by id
$route['routes/(:num)']['get'] = 'routes/getRoute/$1';
$route['routes/(:num)/points']['get'] = 'routes/getPoints/$1';
$route['routes/(:num)']['delete'] = 'routes/deleteRoute/$1';
$route['routes/(:num)']['put'] = 'routes/updateRoute/$1';

/*
	Bus
*/
$route['routes/(:num)/buses']['get'] = 'bus/getBuses/$1';
$route['routes/(:num)/buses']['post'] = 'bus/addBus/$1';

//by id
$route['routes/(:num)/buses/(:num)']['get'] = 'bus/getBus/$1/$2';
$route['routes/(:num)/buses/(:num)']['delete'] = 'bus/deleteBus/$1/$2';
//$route['routes/(:num)/bus/(:num)']['put'] = 'bus/updateBus/$1$2';//TODO

//bus position
$route['routes/(:num)/buses/(:num)/positions']['get'] = 'bus/getLocalizations/$1/$2';
$route['routes/(:num)/buses/(:num)/positions']['post'] = 'bus/addLocalization/$1/$2';
$route['routes/(:num)/buses/(:num)/positions']['delete'] = 'bus/deleteLocalizations/$1/$2';

/*
	Messages
*/
$route['routes/(:num)/messages']['post'] = 'messages/addMessage/$1/$2';
$route['routes/(:num)/messages']['get'] = 'messages/getMessages/$1/$2';
$route['routes/(:num)/messages/register']['post'] = 'messages/registerNotification/$1';
$route['routes/(:num)/buses/(:num)/messages']['post'] = 'messages/addMessage/$1/$2';
$route['routes/(:num)/buses/(:num)/messages']['get'] = 'messages/getMessages/$1/$2';

/*
	Users
*/
$route['users']['post'] = 'user/create';
$route['users']['get'] = 'user/getUser';
$route['users/tokens']['post'] ='user/getToken';