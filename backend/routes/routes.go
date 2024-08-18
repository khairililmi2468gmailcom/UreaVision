package routes

import (
	"database/sql"

	"chart_pagar/controllers"

	"github.com/gin-gonic/gin"
)

func InitializeRoutes(router *gin.Engine, db *sql.DB) {
	// Data route
	router.GET("/data", func(c *gin.Context) {
		controllers.GetData(c, db)
	})
	router.GET("/all-columns", func(c *gin.Context) {
		controllers.GetAllColumnNames(c, db)
	})
}
