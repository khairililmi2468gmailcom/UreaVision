# README

## Overview

This repository is designed to support the Pantau & Analisis Data Produksi Pupuk Real-time project for PT. Pupuk Indonesia Lhokseumawe. The project is structured into two main components:

1. **Backend** - Developed using Go (Golang) to efficiently handle large-scale data processing with concurrency.
2. **Frontend** - Built using CodeIgniter 4 (PHP framework) to align with the existing frameworks used within PT. Pupuk Indonesia.

The system provides clear graphical and diagrammatic representations of production data, aimed at improving efficiency in real-time.

## Project Structure

```
├── backend
│   ├── controllers
│   ├── go.mod
│   ├── go.sum
│   ├── install.txt
│   ├── main.go
│   ├── models
│   └── routes
└── frontend
    ├── app
    ├── builds
    ├── composer.json
    ├── composer.lock
    ├── LICENSE
    ├── phpunit.xml.dist
    ├── preload.php
    ├── public
    ├── README.md
    ├── spark
    ├── tests
    ├── vendor
    └── writable
```

### Backend

The backend of this project is developed in **Go (Golang)**. Go is chosen for its performance and ability to handle high concurrency, which is essential for processing millions of data points efficiently.

#### Running the Backend

To run the backend, execute the following command from the `backend` directory:

```bash
go run main.go
```

This will start the Go server and make it ready to handle requests.

### Frontend

The frontend of this project is built using **CodeIgniter 4**, a PHP framework known for its simplicity and speed. This aligns with the current technology stack used by PT. Pupuk Indonesia Lhokseumawe, ensuring compatibility and ease of maintenance.

#### Running the Frontend

To run the frontend, navigate to the `frontend` directory and use the following command:

```bash
php spark serve
```

This will start the CodeIgniter development server, which can be accessed via `http://localhost:8080`.

## Features

- **Real-time Data Monitoring:** The application allows users to monitor fertilizer production data in real-time.
- **Graphical Analysis:** Provides clear and detailed graphs and diagrams to help in decision-making and improving efficiency.
- **High Performance:** The backend is optimized for handling large datasets with Golang's concurrency capabilities.
- **Ease of Use:** The frontend is built with CodeIgniter 4, providing a familiar environment for the development team at PT. Pupuk Indonesia.

## Technologies Used

- **Golang:** For backend services, chosen for its performance and concurrency support.
- **CodeIgniter 4:** For frontend development, chosen to align with existing company frameworks.

## Installation

### Backend

1. Ensure you have Go installed on your system.
2. Navigate to the `backend` directory.
3. Run `go mod tidy` to install the necessary dependencies.
4. Use `go run main.go` to start the backend server.

### Frontend

1. Ensure you have PHP installed on your system.
2. Navigate to the `frontend` directory.
3. Run `composer install` to install the necessary dependencies.
4. Use `php spark serve` to start the frontend server.

## Contributing

Contributions are welcome! Please submit a pull request or open an issue to discuss your ideas.

## License

This project is licensed under the [MIT License](LICENSE).

## Contact

For any inquiries or further information, please contact the development team at PT. Pupuk Indonesia Lhokseumawe.
