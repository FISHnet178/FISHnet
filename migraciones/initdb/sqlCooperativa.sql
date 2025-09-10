CREATE DATABASE IF NOT EXISTS Cooperativa;
USE Cooperativa;

CREATE TABLE IF NOT EXISTS Terreno ( 
    TerrID INT AUTO_INCREMENT PRIMARY KEY, 
    NombreT VARCHAR(50) NOT NULL, 
    FechaConstruccion DATE NOT NULL, 
    TipoTerreno VARCHAR(30) NOT NULL, 
    Calle VARCHAR(50) NOT NULL, 
    NumeroPuerta INT NOT NULL 
);

CREATE TABLE IF NOT EXISTS UnidadHabitacional ( 
    UnidadID INT AUTO_INCREMENT PRIMARY KEY, 
    TerrID INT UNIQUE NOT NULL, 
    NumeroU INT NOT NULL, 
    Estado VARCHAR(30) NOT NULL, 
    Piso INT NOT NULL, 
    FOREIGN KEY (TerrID) REFERENCES Terreno(TerrID) 
);

CREATE TABLE IF NOT EXISTS Habitante ( 
    HABID INT AUTO_INCREMENT PRIMARY KEY, 
    Usuario VARCHAR(30) NOT NULL, 
    Contrasena VARCHAR(255) NOT NULL, 
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, 
    NombreH VARCHAR(30) NULL, 
    ApellidoH VARCHAR(30) NULL, 
    CI VARCHAR(20) NULL, 
    aprobado TINYINT(1) NOT NULL DEFAULT 0, 
    fecha_aprobacion DATETIME NULL, 
    UnidadID INT NULL, 
    foto_perfil LONGBLOB NULL,
    FOREIGN KEY (UnidadID) REFERENCES UnidadHabitacional(UnidadID) 
);

CREATE TABLE IF NOT EXISTS SalonComunal ( 
    SalonID INT PRIMARY KEY, 
    TerrID INT UNIQUE NOT NULL, 
    Estado VARCHAR(30) NOT NULL, 
    NumeroS INT NOT NULL, 
    HorInicio INT NOT NULL, 
    HorFin INT NOT NULL, 
    FOREIGN KEY (TerrID) REFERENCES Terreno(TerrID) 
);

CREATE TABLE IF NOT EXISTS Jornadas ( 
    JorID INT AUTO_INCREMENT PRIMARY KEY, 
    Tipo VARCHAR(30) NOT NULL, 
    Horas INT NOT NULL, 
    FechaInicio DATE NOT NULL, 
    FechaFin DATE NULL 
);

CREATE TABLE IF NOT EXISTS PagoCuota ( 
    PagoID INT AUTO_INCREMENT PRIMARY KEY, 
    Comprobante LONGBLOB NOT NULL,
    AprobadoP TINYINT(1) NULL, 
    fecha_aprobacionP DATE NULL 
);

CREATE TABLE IF NOT EXISTS Realizan ( 
    HabID INT NOT NULL, 
    JorID INT NOT NULL, 
    PRIMARY KEY (HabID, JorID), 
    FOREIGN KEY (HabID) REFERENCES Habitante(HABID), 
    FOREIGN KEY (JorID) REFERENCES Jornadas(JorID) 
);

CREATE TABLE IF NOT EXISTS Donde ( 
    HabID INT NOT NULL, 
    JorID INT NOT NULL, 
    TerrID INT NOT NULL, 
    PRIMARY KEY (HabID, JorID, TerrID), 
    FOREIGN KEY (HabID, JorID) REFERENCES Realizan(HabID, JorID), 
    FOREIGN KEY (TerrID) REFERENCES Terreno(TerrID) 
);

CREATE TABLE IF NOT EXISTS Efectua_pago ( 
    HabID INT NOT NULL, 
    PagoID INT NOT NULL, 
    aprobadoEP TINYINT(1) NULL,
    PRIMARY KEY (HabID, PagoID), 
    FOREIGN KEY (HabID) REFERENCES Habitante(HABID), 
    FOREIGN KEY (PagoID) REFERENCES PagoCuota(PagoID) 
);

CREATE TABLE IF NOT EXISTS Es_Asignado ( 
    HabID INT NOT NULL, 
    UnidadID INT NOT NULL, 
    PRIMARY KEY (HabID, UnidadID), 
    FOREIGN KEY (HabID) REFERENCES Habitante(HABID), 
    FOREIGN KEY (UnidadID) REFERENCES UnidadHabitacional(UnidadID) 
);

CREATE TABLE IF NOT EXISTS Postulaciones (
  PosID INT AUTO_INCREMENT PRIMARY KEY,
  HabID INT,
  nombre VARCHAR(255),
  email VARCHAR(255),
  telefono VARCHAR(50),
  fecha_nacimiento DATE,
  habitante_uruguay ENUM('si','no'),
  motivo TEXT,
  comprobante_ingreso LONGBLOB,
  comprobante_tipo VARCHAR(100),
  cantidad_ingresan INT,
  fecha_postulacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  FOREIGN KEY (HabID) REFERENCES Habitante(HABID) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS Integrantes (
  IntID INT AUTO_INCREMENT PRIMARY KEY,
  PosID INT,
  nombre VARCHAR(100),
  apellido VARCHAR(100),
  edad INT,
  ci VARCHAR(20),
  FOREIGN KEY (PosID) REFERENCES postulaciones(PosID) ON DELETE CASCADE
);
