package main

import (
	"database/sql"
	"fmt"
	"log"

	"chart_pagar/routes"

	"github.com/gin-gonic/gin"
	_ "github.com/go-sql-driver/mysql"
)

const (
	DBUser     = "makanan"
	DBPassword = "passwordMakanan"
	DBName     = "chart_trend_pagar"
	DBHost     = "localhost"
	DBPort     = "3306"
)

func main() {
	// Database connection setup
	db, err := sql.Open("mysql", fmt.Sprintf("%s:%s@tcp(%s:%s)/%s", DBUser, DBPassword, DBHost, DBPort, DBName))
	if err != nil {
		log.Fatal(err)
	}
	defer db.Close()

	// Initialize router
	router := gin.Default()

	// Initialize routes with the database connection
	routes.InitializeRoutes(router, db)

	// Start server
	router.Run(":5003")
}
