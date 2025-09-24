<?php
session_start();
if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['role']) {
        case 'Admin':
            header("Location: config/dashboard/admin.php");
            break;
        case 'Staff':
            header("Location: config/dashboard/staff.php");
            break;
        case 'Donor':
            header("Location: config/dashboard/donor.php");
            break;
        case 'Seeker':
            header("Location: config/dashboard/seeker.php");
            break;
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kidapawan City Blood Center</title>
    <link rel="icon" type="image/png" href="logo.png" style="border-radius: 50%;">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html, body {
            height: 100%;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: url('kidapawanbloodcenter.jpg') no-repeat center center;
            background-size: cover;
            background-attachment: fixed;
            min-height: 100vh;
            position: relative;
        }
         body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.05);
            z-index: -1;
        }
        
        .navbar {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            background-color: rgba(255, 255, 255, 0.3) !important;
        }
         
        .navbar-brand img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 4px solid #dc3545;
            object-fit: cover;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.3));
        }
        
          .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            background: rgba(255, 255, 255, 0.2);
        }
      
        .hero-section {
            background: rgba(248, 249, 250, 0.1);
            color: #333;
            padding: 80px 0;
            text-align: center;
        }
        .hero-section h1 {
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);
    color: #fff;
}
        .hero-section h6 {
    text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.7);
    color: #fff;
}
        .card:hover {
            transform: translateY(-5px);
        }
        
          .bg-light {
            background: rgba(248, 249, 250, 0.1) !important;
        }
        .bg-light h2 {
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
    color: #333;
}
       .bg-light p {
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
}
      .card h5 {
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
}
    .card p {
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
}
   
      #contact h3 {
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);
    color: #fff;
}
        #contact div {
    text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.7);
    color: #fff;
}
       footer {
            background: rgba(33, 37, 41, 0.8) !important;
        }
        
        .btn-custom {
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
        }
        .text-red {
    color: #dc3545 !important;
}

.text-blood-red {
    color: #dc2626 !important;
}
    </style>
</head>
<body>
    
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #ffffff; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="#" style="color: #dc3545;">
                <img src="logo.png" alt="Blood Center Logo" class="me-3">
                <span style="font-size: 1.4rem;"><i class="fas fa-heart text-danger"></i> Kidapawan City Blood Center</span>
            </a>           
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" style="border-color: #dc3545;">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">
                    <a class="nav-link btn btn-danger ms-2 text-white" href="login.php">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section" id="home">
        <div class="container">
            <h1 class="display-4 fw-bold mb-4 text-danger">Save Lives Through Blood Donation</h1>
<h6 class="lead mb-4 text-danger">Join our community of life-savers. Register as a donor or patient today.</h6></div>
            <div class="mt-4">
                <a href="register.php" class="btn btn-danger btn-lg btn-custom me-3">
                    <i class="fas fa-user-plus"></i> Register Now
                </a>
            </div>
        </div>
    </section>

      
    <section class="py-5" id="contact">
        <div class="container">
            <div class="row">
                              <div class="col-md-6">
                    <h3 class="fw-bold mb-4 text-white">Contact Information</h3>
                    <div class="mb-3">
                        <i class="fas fa-map-marker-alt text-white me-3"></i>
                        <strong class="text-white">Address:</strong> <span class="text-white">Kidapawan City, Cotabato, Philippines</span>
                    </div>
                    <div class="mb-3">
                        <i class="fas fa-phone text-white me-3"></i>
                        <strong class="text-white">Phone:</strong> <span class="text-white">+63 909 032 5551</span>
                    </div>
                    <div class="mb-3">
                        <i class="fas fa-envelope text-white me-3"></i>
                        <strong class="text-white">Email:</strong> <span class="text-white">kidapawancitybloodcenter@gmail.com</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body text-center">
                            <h5 class="text-dark">Emergency Blood Request</h5>
                            <p class="text-dark">For urgent blood requirements, contact us immediately</p>
                            <a href="tel:+639090325551" class="btn btn-danger btn-custom">
                                <i class="fas fa-phone"></i> Emergency Hotline
                            </a>
                        </div>
                    </div>
                </div>  
            </div>
        </div>
    </section>
    
    </section>

    
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-heart text-danger"></i> Kidapawan City Blood Center</h5>
                    <p class="text-muted">Connecting donors with patients to save lives</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-muted mt-2 mb-0">&copy; 2025 Kidapawan City Blood Center</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
       
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });
    </script>
</body>
</html>