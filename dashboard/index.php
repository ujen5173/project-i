<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <title>E-commerce Admin Dashboard</title>
  <link rel="stylesheet" href="./dashboard.css" />
</head>

<body>
  <div class="sidebar">
    <div class="sidebar-logo">
      <h2>Elegance</h2>
    </div>
    <ul class="sidebar-menu">
      <li><a href="#">Dashboard</a></li>
      <li class="has-dropdown">
        <a href="#">Products ▼</a>
        <div class="dropdown">
          <a href="#">Add Products</a>
          <a href="#">View Products</a>
        </div>
      </li>
      <li><a href="#">Orders</a></li>
      <li><a href="#">Customers</a></li>
    </ul>
  </div>

  <div class="main-content">
    <div class="dashboard-header">
      <h1>Dashboard</h1>
      <div>Welcome, Admin</div>
    </div>

    <div class="stats-container">
      <div class="stat-card">
        <h3>Total Revenue</h3>
        <div class="number">NPR-0</div>
      </div>
      <div class="stat-card">
        <h3>Orders</h3>
        <div class="number">1</div>
      </div>
      <div class="stat-card">
        <h3>Products</h3>
        <div class="number">1</div>
      </div>
      <div class="stat-card">
        <h3>Customers</h3>
        <div class="number">1</div>
      </div>
    </div>

    <div class="recent-orders">
      <h2>Recent Orders</h2>
      <table class="order-table">
        <thead>
          <tr>
            <th>Order ID</th>
            <th>CuStomer</th>
            <th>Date</th>
            <th>Status</th>
            <th>Total</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>#12345</td>
            <td>Muji</td>
            <td>2024-02-15</td>
            <td>Completed</td>
            <td>$247.50</td>
          </tr>
          <tr>
            <td>#12346</td>
            <td>Sale</td>
            <td>2024-02-16</td>
            <td>Pending</td>
            <td>$189.99</td>
          </tr>
          <tr>
            <td>#12347</td>
            <td>Condo</td>
            <td>2024-02-17</td>
            <td>Processing</td>
            <td>$345.25</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</body>

</html>