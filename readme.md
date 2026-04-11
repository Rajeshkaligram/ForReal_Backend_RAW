# Rent a Suit
API version 1.0

Last Update: May 20, 2020


## Introduction
The **Rent A Suit API** is designed to allow third party access to our data and services in order to allow extending and providing services beyond the scope provided by regular **Rent A Suit** use.

## URLs
- Base URL staging: [https://rentasuit.sandboxbuild.com/api/v1.0](https://rentasuit.sandboxbuild.com/api/v1.0)

- Base URL production: [https://rentasuit.ca/api/v1.0](https://rentasuit.ca/api/v1.0)


## Authentication
To get access the API resources, a OAuth `api_token` has to generate and send it in API `headers`.

### How to generate token?
To generate the access token, call `/signin` API with your credentials (email, password). If the credentials are valid then a access token will generate and send back in response:

**Sample Response:**
```
{
    "status": 200,
    "message": "You are logged in",
    "data": {
        "api_token": "eyJ0eXAiOiJ...",
        "id": 449,
        "status": 1,
        "first_name": "John",
        "last_name": "Doe",
        "profile_picture": "http://rentasuit.ca/uploads/others/no_avatar.jpg",
        "profile_picture_custom_size": "http://rentasuit.ca/uploads/others/no_avatar.jpg",
        "firebase_id": ""
    }
}
```

**Note:** See `api_token` in above sample response which has to send in API `headers`

### How to put API token in header?

The generated `api_token` is a `Bearer` token and has to send as `Authorization` header, see following example:
**Example:**
```
curl --location --request POST '{{baseUrl}}/profile' \
--header 'Accept: application/json' \
--header 'Authorization: Bearer eyJ0eXAiOiJ...'
```

## How to place an order?

**1.** Generate API token using API `/signin` and put `api_token` in subsequent API calls

**2.** Add items into the cart using API `/cart/add`

**3.** Generate Payment URL using API `checkout/generate-payment-url`. This API response with `payment_url` and `payment_key`, see example response below: 
```
{
  "status": 200,
  "message": "Payment URL has been generated",
  "data": {
    "payment_key": "PAYID-L3BEKTQ8UX78957AF0435538",
    "payment_url": "https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=EC-7TT25263FS3906311"
  }
}
```

**4.** Take `payment_url` and redirect users to this URL. By visiting this URL, users will see a PayPal checkout page along with Cart and Payment details.
    **4.1** After successful payment, users redirect to Order Details page where they can see the list items they ordered.
    
**5.** To check the payment status after redirecting users to the **Payment URL**, use API `/checkout/payment-status`. This API requires a `payment_key` which has generated in above at step#3, see example response of this API call:
```
{
  "status": 200,
  "message": "Payment has been received",
  "data": {
    "pay_key": "PAYID-L3BEKTQ8UX78957AF0435538"
  }
}
```

For more details, please check [API documentation](https://documenter.getpostman.com/view/1788176/SzmmVEm6)
# ForReal_Backend_RAW
