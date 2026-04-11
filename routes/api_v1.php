<?php
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::group(['prefix' => 'v1.0', 'namespace' => 'Api'], function() {
	
	# Auth
	Route::post("signin", 'UserController@login');
	Route::post("signup", 'UserController@register');
	Route::post("signin/social", 'UserController@postSigninSocial');
	Route::post("signin/facebook", 'UserController@postSigninFacebook');
	Route::post("signin/twitter", 'UserController@postSigninTwitter');
	Route::post("forgot-password", 'UserController@postForgotpassword');
	
	# Categories
	Route::get("category-list", 'UserController@getCategoryList');
	
	# Products
	Route::get("product-list", 'UserController@getProductList');
	Route::get("product-search", 'UserController@getProductSearch');
	Route::get("new-added-product", 'UserController@getNewProducts');
	Route::get("product-list-filter", 'UserController@getProductListFilter');
	Route::get("product-detail", 'UserController@getProductDetail');

	# Shipping Calculator
	Route::get("shipping-calculator", 'ShippingCalculatorController@getShippingCalculator');

	# Contact Us
	Route::post("contact-us", 'UserController@postContactUs');
		
	# Common Dropdowns
	Route::get("common-dropdowns", 'CommonDropdownController@getCommonDropdown');

	# Product Reviews
	Route::get("reviews", 'UserController@getReviewList');

	# Cleaners
	Route::get("cleaner-list", 'UserController@getCleanerList');
		
	# FAQs
	Route::get("faqs-list", 'UserController@getFAQsList');

	/*
	|--------------------------------------------------------------------------
	| Routes under user authentication
	|--------------------------------------------------------------------------
	|
	|
	*/
	Route::group(['middleware' => 'auth.api'], function() {

		# user
		Route::get("profile", 'UserProfileController@getProfile');
		Route::post("profile-update", 'UserProfileController@postProfile');
		Route::post("update-firebase-id", 'UserProfileController@postUpdateFireBaseId');
		Route::post("change-password", 'UserProfileController@postChangePassword');
		Route::post("verify-code", 'UserController@postVerifyCode');
		Route::post("resend-verification-code", 'UserController@postResendVerificationCode');
		Route::post("logout", 'UserController@postLogout');
		
		# Wishlist
		Route::post("product-add-wishlist", 'UserController@productAddToWishlist');
		Route::delete("product-remove-wishlist", 'UserController@productRemoveFromWishlist');

		# Notifications
		Route::get("notification-list", 'NotificationController@getNotificationList');

		# Cart
		Route::get("cart/list", 'CartController@getCartList');
		Route::post("cart/add", 'CartController@addItemToCart');
		Route::delete("cart/remove", 'CartController@removeItemFromCart');
		Route::delete("cart/flush", 'CartController@emptyCart');

		# Checkout
		Route::post("checkout/generate-payment-url", 'CartController@getPaymentUrl');
		Route::post("checkout/payment-status", 'CartController@getPaymentStatus');
		//Route::post("generate-pay-id", 'CartController@generatePayId');
		//Route::post("order/place", 'CartController@placeOrder');
//		Route::

		# Messages
		Route::get("messages", 'UserController@getMessages');
		Route::get("room-messages", 'UserController@roomMessages');

		# Product Reviews
		Route::post("submit-product-review", 'UserController@submitProductReview');
		
		# Rented Products
		Route::get("rented-list", 'RentedController@getRentedList');
		Route::get("rented-detail", 'RentedController@getRentedDetail');
		Route::post("change-rented-product-status", 'RentedController@changeRentedProductStatus');
		Route::post("proceed-to-payment", 'RentedController@proceedToPayment');
		Route::get("transactions", 'RentedController@getTransactionList');
		Route::get("transaction-detail", 'RentedController@transactionDetail');

		# Wishlist
		Route::get("wish-list", 'WishListController@getWishList');

		# Products
		Route::get("my-added-products", 'PostItemController@getMyAddedProducts');
		Route::post("product", 'PostItemController@addProduct');
		Route::post("product/update", 'PostItemController@editProduct');
		Route::delete("product/remove", 'PostItemController@removeProduct');
		Route::get("product/{itemId}", 'PostItemController@getEditPostItemDetail');

		# Product Photos
		Route::post("product/photo/upload", 'PostItemController@uploadProductPhoto');
		Route::delete("product/photo/remove", 'PostItemController@removeProductPhoto');

		# Bookings
		Route::get("booking-list", 'BookingController@getBookingList');
	});

});