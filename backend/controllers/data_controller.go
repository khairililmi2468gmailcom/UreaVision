package controllers

import (
	"database/sql"
	"log"
	"net/http"
	"sync"
	"time"

	"chart_pagar/models"

	"github.com/gin-gonic/gin"
)

const (
	WorkerCount = 20 // Number of workers
)

func GetData(c *gin.Context, db *sql.DB) {
	date := c.DefaultQuery("date", time.Now().Format("2006-01-02"))
	column := c.DefaultQuery("column", "Time_Stamp")

	startTime, err := time.Parse("2006-01-02", date)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "Invalid date format"})
		return
	}
	endTime := startTime.Add(24 * time.Hour)

	tables, err := models.GetTables(db)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	results := make(chan *models.TableColumnData, len(tables))
	var wg sync.WaitGroup

	// Worker pool for concurrent data fetching
	jobChan := make(chan string, len(tables))
	for i := 0; i < WorkerCount; i++ {
		wg.Add(1)
		go worker(db, jobChan, results, &wg, column, startTime, endTime)
	}

	// Add jobs to the job channel
	for _, table := range tables {
		jobChan <- table
	}
	close(jobChan)

	wg.Wait()
	close(results)

	combinedData := models.CombineTableData(results)

	c.JSON(http.StatusOK, combinedData)
}

// Worker function to fetch data concurrently
func worker(db *sql.DB, jobChan chan string, results chan *models.TableColumnData, wg *sync.WaitGroup, column string, startTime, endTime time.Time) {
	defer wg.Done()

	for tableName := range jobChan {
		batchResults, err := models.GetTableDataInBatches(db, tableName, column, startTime, endTime)
		if err != nil {
			log.Println("Error fetching data from table", tableName, ":", err)
			continue
		}
		results <- batchResults
	}
}


func GetAllColumnNames(c *gin.Context, db *sql.DB) {
	columnNames, err := models.GetAllColumnNames(db)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{"columns": columnNames})
}