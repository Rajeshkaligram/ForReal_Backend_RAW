<?php
$omari = "";

if ($omari != "omari") { 
      $name = $_POST['name'];
      $email = "info@rentassuit.ca";
      $ToEmail = 'omarilamar@gmail.com';
      $EmailSubject = "Item Added"; 
      $mailheader = "From: ".$_POST["email"]."\r\n"; 
      $mailheader .= "Reply-To: ".$_POST["email"]."\r\n"; 
      $mailheader .= "Content-type: text/html; charset=iso-8859-1\r\n"; 
      $message_body     .= "Email: ".$_POST["email"]."<br>"; 
      $message_body     .= "Subject:".$_POST['subject']."<br />";  
      $message_body     .= "picture: ".$_POST["picture"]."<br>";
      $message_body     .= "Name: ".$_POST["name"]."<br>";
      $message_body     .= "Retail Price: ".$_POST["retail_price"]."<br>";
      $message_body     .= "Price: ".$_POST["price"]."<br>";
      $message_body     .= "Price Week: ".$_POST["price_week"]."<br>";
      $message_body     .= "File: ".$_POST["file"]."<br>";
      $message_body     .= "Color: ".$_POST["color"]."<br>";
      $message_body     .= "Size: ".$_POST["size"]."<br>";
      $message_body     .= "Categories: ".$_POST["categories"]."<br>";
      $message_body     .= "Alterations: ".$_POST["alteration"]."<br>";
      $message_body     .= "Condition: ".$_POST["condition"]."<br>";
      $message_body     .= "Season: ".$_POST["season"]."<br>";
      $message_body     .= "Description: ".$_POST["description"]."<br>";
      $message_body     .= "Designer: ".$_POST["designer"]."<br>";
      $message_body     .= "Canellation: ".$_POST["cancellation"]."<br>";
      if(mail($ToEmail, $EmailSubject, $message_body, $mailheader)) {
      echo "<script>alert('Thanks Item will be added !');</script>";
      echo "<script>document.location.href='https://rentasuit.ca/dev/public/for-rent/post-an-item'</script>";
      }
      else {
      echo "<script>alert('Mail was not sent. Please try again later');</script>";
      }
     }