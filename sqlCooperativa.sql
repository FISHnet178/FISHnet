CREATE TABLE Terreno (
    TerrID            INT AUTO_INCREMENT PRIMARY KEY,
    NombreT           VARCHAR(50)  NOT NULL,
    FechaConstruccion DATE         NOT NULL,
    TipoTerreno       VARCHAR(30)  NOT NULL,
    Calle         VARCHAR(50)   NOT NULL,
    NumeroPuerta  INT           NOT NULL
);

CREATE TABLE UnidadHabitacional (
    UnidadID   INT AUTO_INCREMENT PRIMARY KEY,
    TerrID     INT          UNIQUE NOT NULL,
    NumeroU    INT          NOT NULL,
    Estado     VARCHAR(30)  NOT NULL,
    Piso       INT          NOT NULL,
    FOREIGN KEY (TerrID)
        REFERENCES Terreno(TerrID)
);

CREATE TABLE Habitante (
	Usuario	VARCHAR(30)	NOT NULL,
    Contraseña	VARCHAR(30)	NOT NULL,
    NombreH	VARCHAR(30)	NOT NULL,
    ApellidoH	VARCHAR(30) NOT NULL,
    HABID INT AUTO_INCREMENT PRIMARY KEY,
    UnidadID        INT          NOT NULL,
    FOREIGN KEY (UnidadID)
        REFERENCES UnidadHabitacional(UnidadID)
);

CREATE TABLE SalonComunal (
    SalonID   INT          PRIMARY KEY,
    TerrID    INT          UNIQUE NOT NULL,
    Estado    VARCHAR(30)  NOT NULL,
    NumeroS   INT          NOT NULL,
    HorInicio INT         NOT NULL,
    HorFin    INT         NOT NULL,	
    FOREIGN KEY (TerrID)
        REFERENCES Terreno(TerrID)
);


CREATE TABLE Jornadas (
    JorID   INT AUTO_INCREMENT PRIMARY KEY,
    Tipo    VARCHAR(30)  NOT NULL,
    Horas	INT	NOT NULL,
    FechaInicio INT          NOT NULL,
    FechaFin    INT          
);

CREATE TABLE PagoCuota (
    PagoID INT AUTO_INCREMENT PRIMARY KEY,
    Medio  VARCHAR(30)     NOT NULL,
    FechaP DATE            NOT NULL,
    Monto  DECIMAL(10,2)   NOT NULL
);

CREATE TABLE Realizan (
    HabID INT NOT NULL,
    JorID INT NOT NULL,
    PRIMARY KEY (HabID, JorID),
    FOREIGN KEY (HabID)
        REFERENCES Habitante(HabID),
    FOREIGN KEY (JorID)
        REFERENCES Jornadas(JorID)
);

CREATE TABLE Donde (
    HabID  INT NOT NULL,
    JorID  INT NOT NULL,
    TerrID INT NOT NULL,
    PRIMARY KEY (HabID, JorID, TerrID),
    FOREIGN KEY (HabID, JorID)
        REFERENCES Realizan(HabID, JorID),
    FOREIGN KEY (TerrID)
        REFERENCES Terreno(TerrID)
);

CREATE TABLE Efectua_pago (
    HabID  INT NOT NULL,
    PagoID INT NOT NULL,
    PRIMARY KEY (HabID, PagoID),
    FOREIGN KEY (HabID)
        REFERENCES Habitante(HabID),
    FOREIGN KEY (PagoID)
        REFERENCES PagoCuota(PagoID)
);

CREATE TABLE Es_Asignado (
    HabID  INT NOT NULL,
    UnidadID INT NOT NULL,
    PRIMARY KEY (HabID, UnidadID),
    FOREIGN KEY (HabID)
        REFERENCES Habitante(HabID),
    FOREIGN KEY (UnidadID)
        REFERENCES UnidadHabitacional(UnidadID)
);

ALTER TABLE Habitante
ADD COLUMN aprobado TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE Habitante
  ADD COLUMN fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
  AFTER contraseña;
  
ALTER TABLE Habitante
  ADD COLUMN fecha_aprobacion DATETIME NULL AFTER aprobado;
  

ALTER TABLE Habitante
  MODIFY COLUMN NombreH   VARCHAR(30) NULL,
  MODIFY COLUMN ApellidoH VARCHAR(30) NULL,
  MODIFY COLUMN UnidadID  INT          NULL;

ALTER TABLE Habitante
  ADD COLUMN CI VARCHAR(20) NULL AFTER ApellidoH;
  
ALTER TABLE Habitante
  MODIFY COLUMN `Contraseña` VARCHAR(255) NOT NULL;