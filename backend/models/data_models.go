package models

import (
	"database/sql"
	"encoding/base64"
	"fmt"
	"time"
)

const (
	PageSize  = 86400 // 86,400 rows per day
	BatchSize = 1000  // Split data retrieval into batches
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
		Columns:   []string{"Time_Stamp", column},
		Data:      [][]interface{}{},
	}

	for offset := 0; offset < PageSize; offset += BatchSize {
		batchData, err := GetTableData(db, tableName, column, startTime, endTime, offset, BatchSize)
		if err != nil {
			return nil, err
		}
		columnData.Data = append(columnData.Data, batchData.Data...)
	}

	return columnData, nil
}

func GetTableData(db *sql.DB, tableName, column string, startTime, endTime time.Time, offset, limit int) (*TableColumnData, error) {
	query := fmt.Sprintf("SELECT Time_Stamp, %s FROM %s WHERE Time_Stamp BETWEEN ? AND ? LIMIT ? OFFSET ?", column, tableName)

	rows, err := db.Query(query, startTime, endTime, limit, offset)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	columnData := &TableColumnData{
		TableName: tableName,
		Columns:   []string{"Time_Stamp", column},
	}

	for rows.Next() {
		var timestampBytes []byte
		var value interface{}
		if err := rows.Scan(&timestampBytes, &value); err != nil {
			return nil, err
		}

		// Convert timestampBytes to time.Time
		var formattedTime string
		if len(timestampBytes) > 0 {
			timestampString := string(timestampBytes)
			parsedTime, err := time.Parse("2006-01-02 15:04:05", timestampString)
			if err != nil {
				return nil, err
			}
			formattedTime = parsedTime.Format("2006-01-02 15:04:05")
		} else {
			formattedTime = "NULL"
		}

		// Handle column value formatting
		if column == "Time_Stamp" {
			value = formattedTime
		} else {
			if valueBytes, ok := value.([]byte); ok {
				decoded, _ := base64.StdEncoding.DecodeString(string(valueBytes))
				value = string(decoded)
			}
		}

		columnData.Data = append(columnData.Data, []interface{}{formattedTime, value})
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

			// Use sql.NullString to handle potential NULL values
			if err := rows.Scan(&columnName, &colType, &null, &key, &defaultValue, &extra); err != nil {
				return nil, err
			}

			// Convert valid sql.NullString to string and add to the set
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