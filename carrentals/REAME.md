# Car Rental Management System Database

## Database Name
`vehiclerental`

## Database Tables

### 1. customers
Customer information and licensing details.

| Column Name | Data Type | Constraints | Description |
|-------------|-----------|-------------|-------------|
| CustomerID | int(11) | PRIMARY KEY | Unique customer identifier |
| Name | varchar(30) | | Customer full name |
| LicenseNumber | varchar(20) | | Driver's license number |
| ContactNumber | varchar(15) | | Phone number |
| Email | varchar(25) | | Email address |
| LicenseImg | varchar(255) | | Additional licensing information |

### 2. vehicles
Vehicle inventory and specifications.

| Column Name | Data Type | Constraints | Description |
|-------------|-----------|-------------|-------------|
| VehicleID | int(11) | PRIMARY KEY | Unique vehicle identifier |
| Model | varchar(20) | | Vehicle model |
| PlateNumber | varchar(10) | | License plate number |
| DailyRate | double | | Daily rental rate |
| Image | varchar(255) | | Vehicle image file path |
| Status | varchar(15) | | Vehicle availability status |

### 3. rentals
Rental transaction records.

| Column Name | Data Type | Constraints | Description |
|-------------|-----------|-------------|-------------|
| RentalID | int(11) | PRIMARY KEY | Unique rental identifier |
| CustomerID | int(11) | FOREIGN KEY | References customers.CustomerID |
| VehicleID | int(11) | FOREIGN KEY | References vehicles.VehicleID |
| PickUpDate | date | | Rental start date |
| ToReturnDate | date | | Expected return date |
| ReturnedDate | date | | Actual return date |
| TotalCost | double | | Total rental cost |
| NoOfDays | int(11) | | Number of rental days |

### 4. distances
Distance tracking for rentals.

| Column Name | Data Type | Constraints | Description |
|-------------|-----------|-------------|-------------|
| DistanceID | int(11) | PRIMARY KEY | Unique distance record identifier |
| RentalID | int(11) | FOREIGN KEY | References rentals.RentalID |
| VehicleID | int(11) | FOREIGN KEY | References vehicles.VehicleID |
| DateRecorded | date | | Date when distance was recorded |
| KmBefore | int(11) | | Odometer reading before rental |
| KmAfter | int(11) | | Odometer reading after rental |
| KmUsed | int(11) | | Total kilometers used |

### 5. issues
Issue tracking and resolution.

| Column Name | Data Type | Constraints | Description |
|-------------|-----------|-------------|-------------|
| IssueID | int(11) | PRIMARY KEY | Unique issue identifier |
| RentalID | int(11) | FOREIGN KEY | References rentals.RentalID |
| VehicleID | int(11) | FOREIGN KEY | References vehicles.VehicleID |
| CustomerID | int(11) | FOREIGN KEY | References customers.CustomerID |
| Description | text | | Issue description |
| DateReported | date | | Date when issue was reported |
| DateResolved | date | | Date when issue was resolved |
| Proof | varchar(255) | | Evidence/proof file path |
| Status | varchar(15) | | Issue status (open, resolved, etc.) |

### 6. users
System user accounts.

| Column Name | Data Type | Constraints | Description |
|-------------|-----------|-------------|-------------|
| id | int(11) | PRIMARY KEY | Unique user identifier |
| username | varchar(30) | | System username |
| password | varchar(255) | | Encrypted password |

## Relationships

- **customers** → **rentals**: One-to-many (CustomerID)
- **vehicles** → **rentals**: One-to-many (VehicleID)
- **rentals** → **distances**: One-to-many (RentalID)
- **rentals** → **issues**: One-to-many (RentalID)
- **vehicles** → **distances**: One-to-many (VehicleID)
- **vehicles** → **issues**: One-to-many (VehicleID)
- **customers** → **issues**: One-to-many (CustomerID)

## Database Schema Notes

1. **Primary Keys**: All tables use auto-incrementing integer primary keys
2. **Foreign Keys**: Maintain referential integrity between related tables
3. **Data Types**: 
   - `int(11)` for numeric IDs and counters
   - `varchar()` for text fields with specified lengths
   - `text` for longer descriptions
   - `date` for date fields
   - `double` for decimal values (costs, rates)
4. **File Storage**: Images and proof files are stored as file paths in varchar fields
5. **Status Fields**: Used for tracking vehicle availability and issue resolution states

# For image uploads
Create the following folders for image uploads and ensure they are readable (e.g., with permission 755):

- `issues/`  (for issue proof images)
- `profiles/` (for customer license images)
- `vehicles/` (for vehicle images)