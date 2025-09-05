CREATE TABLE Terreno ( 
    TerrID INT AUTO_INCREMENT PRIMARY KEY, 
    NombreT VARCHAR(50) NOT NULL, 
    FechaConstruccion DATE NOT NULL, 
    TipoTerreno VARCHAR(30) NOT NULL, 
    Calle VARCHAR(50) NOT NULL, 
    NumeroPuerta INT NOT NULL 
);

CREATE TABLE UnidadHabitacional ( 
    UnidadID INT AUTO_INCREMENT PRIMARY KEY, 
    TerrID INT UNIQUE NOT NULL, 
    NumeroU INT NOT NULL, 
    Estado VARCHAR(30) NOT NULL, 
    Piso INT NOT NULL, 
    FOREIGN KEY (TerrID) REFERENCES Terreno(TerrID) 
);

CREATE TABLE Habitante ( 
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

CREATE TABLE SalonComunal ( 
    SalonID INT PRIMARY KEY, 
    TerrID INT UNIQUE NOT NULL, 
    Estado VARCHAR(30) NOT NULL, 
    NumeroS INT NOT NULL, 
    HorInicio INT NOT NULL, 
    HorFin INT NOT NULL, 
    FOREIGN KEY (TerrID) REFERENCES Terreno(TerrID) 
);

CREATE TABLE Jornadas ( 
    JorID INT AUTO_INCREMENT PRIMARY KEY, 
    Tipo VARCHAR(30) NOT NULL, 
    Horas INT NOT NULL, 
    FechaInicio DATE NOT NULL, 
    FechaFin DATE NULL 
);

CREATE TABLE PagoCuota ( 
    PagoID INT AUTO_INCREMENT PRIMARY KEY, 
    Comprobante LONGBLOB NOT NULL,
    AprobadoP TINYINT(1) NULL, 
    fecha_aprobacionP TINYINT(1) NULL 
);

CREATE TABLE Realizan ( 
    HabID INT NOT NULL, 
    JorID INT NOT NULL, 
    PRIMARY KEY (HabID, JorID), 
    FOREIGN KEY (HabID) REFERENCES Habitante(HABID), 
    FOREIGN KEY (JorID) REFERENCES Jornadas(JorID) 
);

CREATE TABLE Donde ( 
    HabID INT NOT NULL, 
    JorID INT NOT NULL, 
    TerrID INT NOT NULL, 
    PRIMARY KEY (HabID, JorID, TerrID), 
    FOREIGN KEY (HabID, JorID) REFERENCES Realizan(HabID, JorID), 
    FOREIGN KEY (TerrID) REFERENCES Terreno(TerrID) 
);

CREATE TABLE Efectua_pago ( 
    HabID INT NOT NULL, 
    PagoID INT NOT NULL, 
    aprobadoEP TINYINT(1) NULL,
    PRIMARY KEY (HabID, PagoID), 
    FOREIGN KEY (HabID) REFERENCES Habitante(HABID), 
    FOREIGN KEY (PagoID) REFERENCES PagoCuota(PagoID) 
);

CREATE TABLE Es_Asignado ( 
    HabID INT NOT NULL, 
    UnidadID INT NOT NULL, 
    PRIMARY KEY (HabID, UnidadID), 
    FOREIGN KEY (HabID) REFERENCES Habitante(HABID), 
    FOREIGN KEY (UnidadID) REFERENCES UnidadHabitacional(UnidadID) 
);

INSERT INTO Habitante (Usuario, Contrasena, aprobado) VALUES 
('Nahuel', '$2y$10$e0NRG8m7rYk1b5r9H1mE8uJ8Fz8eFz8eFz8eFz8eFz8eFz8eFz8eFz8e', 1);