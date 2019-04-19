# trivago-coding-challenge
Requirements
  1. You need to install XAMPP control panel which you can download from here using this link
      https://www.apachefriends.org/download.html
  2. Then you need to start the Apache and MySQL from the control panel.
  
Files Included
  1. rahul.sql.zip
  2. Link to the project repository
  
Executing the project or How to install and run the application (detailed steps)
  1. First open the XAMPP control panel and start Apache and MyQL.
  2. Then go to http://localhost/phpmyadmin/ then create a databse with the name rahul.
  3. The next step is to import the SQL file to the database. The file rahul.sql.zip needs to be imported as shown in this screenshot.
      https://prnt.sc/ne8swm
  4. After importing the SQL file then copy the whole rahul folder and go to the location where XAMPP has been installed.
      Go to XAMPP -> htdocs and paste the folder here.
  5. Now go to http://localhost/rahul/sourcecode and you should be able to see the login page and successfully running application.
  6. If any FrontEnd seems to be broken then please use this button to flush the cache "Flush All Caches". Then it will flush the cache and 
      and runs the drupal theme successfully.
  7. After starting the application please use the username: admin and password: amdin@ to login to the application.
  8. Orders Page - This will list the details of the order given by customer
  9. Create order page - This will allow the customer to create the order
  10. Wines - This will list all the wines that are fetched from the RSS feed using the URL given.
  
 What has been done
  1. For the taks one . RSS feed imported into drupal nodes using the drupa batch queue. In "Wines" page the list of imported RSS feed data are shown. To manually import RSS feed please click "Import RSS feed" button.
  2. Then creating an order - In the "wines" field if you start typing using ajax autocomplete a list of wines will appaer. Select a wine from the list then using drupal asynchronous queue the avaialbility of wine is checked and a success or error message will displayed to the cusotmer. Avaialbility check is based on the order date equals to wine pusblished date.

What extra requirements have been added
  1. Extra filter haven been added to filter our the wines avialble based on the published date, wine name etc., in the wines page
  2. In the "Orders" page few fiters have been added to filter details based on the order date or customer name or waiter assigned.
  3. Also in the same page if you click on the customer name a pop up window is displayed using ajax modal shows the details of the order given by the customer.
  4. While importing the RSS feed manual a progress bar is added to show how much percentage of the process has been completed and upon completion it will display a message how many wines have been added to the database.
  
Store an application log with relevant actions
  1. To check the applicaiton logs, in the toolbar menu go to Reports -> Recent Log Messages or use this link http://localhost/rahul/sourcecode/admin/reports/dblog.
  
 
