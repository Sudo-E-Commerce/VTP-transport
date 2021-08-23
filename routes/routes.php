<?php
App::booted(function() {
	$namespace = 'Sudo\ViettelPost\Http\Controllers';

	Route::namespace($namespace)->name('admin.')->prefix(config('app.admin_dir'))->middleware(['web', 'auth-admin'])->group(function() {

		Route::post('viettelpost_stores/load-address', 'ViettelPostStroreController@loadAdress')->name('vtpstores.loadAdress');
		// viettelpost_stores
		Route::post('viettelpost_stores/set-default', 'ViettelPostStroreController@setDefault')->name('vtpstores.default');
		Route::match(['GET', 'POST'], 'viettelpost_stores/setAccount', 'ViettelPostStroreController@setAccount')->name('viettelpost_stores.setAccount');
		Route::resource('viettelpost_stores', 'ViettelPostStroreController');
	});
	// Not Auth
	Route::namespace($namespace)->name('app.viettelpost.')->middleware(['web'])->group(function() {
		Route::post('/viettelpost/webhook', 'ViettelPostController@webhook')->name('webhook');
	});
});