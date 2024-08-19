package models

import (
	"database/sql"
	"fmt"
	"log"
	"sync"
	"time"
)

const (
	PageSize    = 1440    // 1,440 minutes in a day
	BatchSize   = 10000    // Batch size for data retrieval
	WorkerCount = 30      // Number of concurrent workers
)

type TableColumnData struct {
	TableName string
	Columns   []string
	Data      [][]interface{}
}

func GetTables(db *sql.DB) ([]string, error) {
	rows, err := db.Query("SHOW TABLES")
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var tables []string
	for rows.Next() {
		var table string
		if err := rows.Scan(&table); err != nil {
			return nil, err
		}
		tables = append(tables, table)
	}
	return tables, nil
}

func GetTableDataInBatches(db *sql.DB, tableName, column string, startTime, endTime time.Time) (*TableColumnData, error) {
	columnData := &TableColumnData{
		TableName: tableName,
		Columns:   []string{"Minute", column},
		Data:      [][]interface{}{},
	}

	jobChan := make(chan int, PageSize/BatchSize)
	resultChan := make(chan [][]interface{}, PageSize/BatchSize)
	var wg sync.WaitGroup

	for i := 0; i < WorkerCount; i++ {
		wg.Add(1)
		go func() {
			defer wg.Done()
			for offset := range jobChan {
				batchData, err := GetTableData(db, tableName, column, startTime, endTime, offset, BatchSize)
				if err != nil {
					log.Println("Error fetching batch data:", err)
					continue
				}
				resultChan <- batchData.Data
			}
		}()
	}

	for offset := 0; offset < PageSize; offset += BatchSize {
		jobChan <- offset
	}
	close(jobChan)

	go func() {
		wg.Wait()
		close(resultChan)
	}()

	for batch := range resultChan {
		columnData.Data = append(columnData.Data, batch...)
	}

	return columnData, nil
}

func GetTableData(db *sql.DB, tableName, column string, startTime, endTime time.Time, offset, limit int) (*TableColumnData, error) {
	query := fmt.Sprintf(`
		SELECT 
			DATE_FORMAT(Time_Stamp, '%%Y-%%m-%%d %%H:%%i:00') AS Minute,
			AVG(%s) AS AverageValue
		FROM %s
		WHERE Time_Stamp BETWEEN ? AND ?
		GROUP BY Minute
		LIMIT ? OFFSET ?
	`, column, tableName)

	rows, err := db.Query(query, startTime, endTime, limit, offset)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	columnData := &TableColumnData{
		TableName: tableName,
		Columns:   []string{"Minute", "AverageValue"},
	}

	for rows.Next() {
		var minute string
		var avgValue float64
		if err := rows.Scan(&minute, &avgValue); err != nil {
			return nil, err
		}

		columnData.Data = append(columnData.Data, []interface{}{minute, avgValue})
	}

	if err = rows.Err(); err != nil {
		return nil, err
	}

	return columnData, nil
}

func CombineTableData(results chan *TableColumnData) map[string]interface{} {
	combinedData := make(map[string]interface{})
	combinedData["columns"] = []string{}
	combinedData["data"] = [][]interface{}{}

	columnSet := make(map[string]bool)
	var data [][]interface{}

	for tableData := range results {
		for _, col := range tableData.Columns {
			colName := tableData.TableName + "." + col
			if !columnSet[colName] {
				columnSet[colName] = true
				combinedData["columns"] = append(combinedData["columns"].([]string), colName)
			}
		}

		data = append(data, tableData.Data...)
	}

	combinedData["data"] = data
	return combinedData
}

func GetAllColumnNames(db *sql.DB) ([]string, error) {
	tables, err := GetTables(db)
	if err != nil {
		return nil, err
	}

	columnSet := make(map[string]bool)

	for _, table := range tables {
		query := fmt.Sprintf("SHOW COLUMNS FROM %s", table)

		rows, err := db.Query(query)
		if err != nil {
			return nil, err
		}
		defer rows.Close()

		for rows.Next() {
			var columnName, colType, null, key, defaultValue, extra sql.NullString

			if err := rows.Scan(&columnName, &colType, &null, &key, &defaultValue, &extra); err != nil {
				return nil, err
			}

			if columnName.Valid {
				columnSet[columnName.String] = true
			}
		}
	}

	var uniqueColumnNames []string
	for col := range columnSet {
		uniqueColumnNames = append(uniqueColumnNames, col)
	}

	return uniqueColumnNames, nil
}
