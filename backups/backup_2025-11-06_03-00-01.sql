/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.6.22-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: db    Database: cooperativa
-- ------------------------------------------------------
-- Server version	8.0.44

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `Donde`
--

DROP TABLE IF EXISTS `Donde`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Donde` (
  `HabID` int NOT NULL,
  `JorID` int NOT NULL,
  `TerrID` int NOT NULL,
  PRIMARY KEY (`HabID`,`JorID`,`TerrID`),
  KEY `TerrID` (`TerrID`),
  CONSTRAINT `Donde_ibfk_1` FOREIGN KEY (`HabID`, `JorID`) REFERENCES `Realizan` (`HabID`, `JorID`),
  CONSTRAINT `Donde_ibfk_2` FOREIGN KEY (`TerrID`) REFERENCES `Terreno` (`TerrID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Donde`
--

LOCK TABLES `Donde` WRITE;
/*!40000 ALTER TABLE `Donde` DISABLE KEYS */;
/*!40000 ALTER TABLE `Donde` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Efectua_pago`
--

DROP TABLE IF EXISTS `Efectua_pago`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Efectua_pago` (
  `HabID` int NOT NULL,
  `PagoID` int NOT NULL,
  `aprobadoEP` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`HabID`,`PagoID`),
  KEY `PagoID` (`PagoID`),
  CONSTRAINT `Efectua_pago_ibfk_1` FOREIGN KEY (`HabID`) REFERENCES `Habitante` (`HABID`),
  CONSTRAINT `Efectua_pago_ibfk_2` FOREIGN KEY (`PagoID`) REFERENCES `PagoCuota` (`PagoID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Efectua_pago`
--

LOCK TABLES `Efectua_pago` WRITE;
/*!40000 ALTER TABLE `Efectua_pago` DISABLE KEYS */;
/*!40000 ALTER TABLE `Efectua_pago` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Es_Asignado`
--

DROP TABLE IF EXISTS `Es_Asignado`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Es_Asignado` (
  `HabID` int NOT NULL,
  `UnidadID` int NOT NULL,
  PRIMARY KEY (`HabID`,`UnidadID`),
  KEY `UnidadID` (`UnidadID`),
  CONSTRAINT `Es_Asignado_ibfk_1` FOREIGN KEY (`HabID`) REFERENCES `Habitante` (`HABID`),
  CONSTRAINT `Es_Asignado_ibfk_2` FOREIGN KEY (`UnidadID`) REFERENCES `UnidadHabitacional` (`UnidadID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Es_Asignado`
--

LOCK TABLES `Es_Asignado` WRITE;
/*!40000 ALTER TABLE `Es_Asignado` DISABLE KEYS */;
/*!40000 ALTER TABLE `Es_Asignado` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Foro`
--

DROP TABLE IF EXISTS `Foro`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Foro` (
  `ForoID` int NOT NULL AUTO_INCREMENT,
  `ParentID` int DEFAULT NULL,
  `titulo` text,
  `asunto` text NOT NULL,
  `HabId` int NOT NULL,
  PRIMARY KEY (`ForoID`),
  KEY `fk_foro_hab` (`HabId`),
  KEY `fk_foro_parent` (`ParentID`),
  CONSTRAINT `fk_foro_hab` FOREIGN KEY (`HabId`) REFERENCES `Habitante` (`HABID`) ON DELETE CASCADE,
  CONSTRAINT `fk_foro_parent` FOREIGN KEY (`ParentID`) REFERENCES `Foro` (`ForoID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Foro`
--

LOCK TABLES `Foro` WRITE;
/*!40000 ALTER TABLE `Foro` DISABLE KEYS */;
/*!40000 ALTER TABLE `Foro` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Habitante`
--

DROP TABLE IF EXISTS `Habitante`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Habitante` (
  `HABID` int NOT NULL AUTO_INCREMENT,
  `Usuario` varchar(30) NOT NULL,
  `Contrasena` varchar(255) NOT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `NombreH` varchar(30) DEFAULT NULL,
  `ApellidoH` varchar(30) DEFAULT NULL,
  `CI` varchar(20) DEFAULT NULL,
  `aprobado` tinyint(1) NOT NULL DEFAULT '0',
  `fecha_aprobacion` datetime DEFAULT NULL,
  `UnidadID` int DEFAULT NULL,
  `foto_perfil` longblob,
  `admin` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`HABID`),
  KEY `UnidadID` (`UnidadID`),
  CONSTRAINT `Habitante_ibfk_1` FOREIGN KEY (`UnidadID`) REFERENCES `UnidadHabitacional` (`UnidadID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Habitante`
--

LOCK TABLES `Habitante` WRITE;
/*!40000 ALTER TABLE `Habitante` DISABLE KEYS */;
/*!40000 ALTER TABLE `Habitante` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Integrantes`
--

DROP TABLE IF EXISTS `Integrantes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Integrantes` (
  `IntID` int NOT NULL AUTO_INCREMENT,
  `PosID` int DEFAULT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `apellido` varchar(100) DEFAULT NULL,
  `edad` int DEFAULT NULL,
  `ci` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`IntID`),
  KEY `PosID` (`PosID`),
  CONSTRAINT `Integrantes_ibfk_1` FOREIGN KEY (`PosID`) REFERENCES `Postulaciones` (`PosID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Integrantes`
--

LOCK TABLES `Integrantes` WRITE;
/*!40000 ALTER TABLE `Integrantes` DISABLE KEYS */;
/*!40000 ALTER TABLE `Integrantes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Jornadas`
--

DROP TABLE IF EXISTS `Jornadas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Jornadas` (
  `JorID` int NOT NULL AUTO_INCREMENT,
  `Tipo` varchar(30) NOT NULL,
  `Horas` int NOT NULL,
  `FechaInicio` date NOT NULL,
  `FechaFin` date DEFAULT NULL,
  PRIMARY KEY (`JorID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Jornadas`
--

LOCK TABLES `Jornadas` WRITE;
/*!40000 ALTER TABLE `Jornadas` DISABLE KEYS */;
/*!40000 ALTER TABLE `Jornadas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PagoCuota`
--

DROP TABLE IF EXISTS `PagoCuota`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `PagoCuota` (
  `PagoID` int NOT NULL AUTO_INCREMENT,
  `Comprobante` longblob NOT NULL,
  `AprobadoP` tinyint(1) DEFAULT NULL,
  `fecha_aprobacionP` date DEFAULT NULL,
  PRIMARY KEY (`PagoID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PagoCuota`
--

LOCK TABLES `PagoCuota` WRITE;
/*!40000 ALTER TABLE `PagoCuota` DISABLE KEYS */;
/*!40000 ALTER TABLE `PagoCuota` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Postulaciones`
--

DROP TABLE IF EXISTS `Postulaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Postulaciones` (
  `PosID` int NOT NULL AUTO_INCREMENT,
  `HabID` int DEFAULT NULL,
  `nombre` varchar(255) DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `habitante_uruguay` enum('si','no') DEFAULT NULL,
  `motivo` text,
  `comprobante_ingreso` longblob,
  `cantidad_ingresan` int DEFAULT NULL,
  `fecha_postulacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`PosID`),
  UNIQUE KEY `HabID` (`HabID`),
  CONSTRAINT `Postulaciones_ibfk_1` FOREIGN KEY (`HabID`) REFERENCES `Habitante` (`HABID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Postulaciones`
--

LOCK TABLES `Postulaciones` WRITE;
/*!40000 ALTER TABLE `Postulaciones` DISABLE KEYS */;
/*!40000 ALTER TABLE `Postulaciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Realizan`
--

DROP TABLE IF EXISTS `Realizan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Realizan` (
  `HabID` int NOT NULL,
  `JorID` int NOT NULL,
  PRIMARY KEY (`HabID`,`JorID`),
  KEY `JorID` (`JorID`),
  CONSTRAINT `Realizan_ibfk_1` FOREIGN KEY (`HabID`) REFERENCES `Habitante` (`HABID`),
  CONSTRAINT `Realizan_ibfk_2` FOREIGN KEY (`JorID`) REFERENCES `Jornadas` (`JorID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Realizan`
--

LOCK TABLES `Realizan` WRITE;
/*!40000 ALTER TABLE `Realizan` DISABLE KEYS */;
/*!40000 ALTER TABLE `Realizan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SalonComunal`
--

DROP TABLE IF EXISTS `SalonComunal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `SalonComunal` (
  `SalonID` int NOT NULL AUTO_INCREMENT,
  `TerrID` int NOT NULL,
  `Estado` varchar(30) NOT NULL,
  `HorInicio` int NOT NULL,
  `HorFin` int NOT NULL,
  PRIMARY KEY (`SalonID`),
  KEY `TerrID` (`TerrID`),
  CONSTRAINT `SalonComunal_ibfk_1` FOREIGN KEY (`TerrID`) REFERENCES `Terreno` (`TerrID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SalonComunal`
--

LOCK TABLES `SalonComunal` WRITE;
/*!40000 ALTER TABLE `SalonComunal` DISABLE KEYS */;
/*!40000 ALTER TABLE `SalonComunal` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Terreno`
--

DROP TABLE IF EXISTS `Terreno`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Terreno` (
  `TerrID` int NOT NULL AUTO_INCREMENT,
  `NombreT` varchar(50) NOT NULL,
  `FechaConstruccion` date NOT NULL,
  `TipoTerreno` varchar(30) NOT NULL,
  `Calle` varchar(50) NOT NULL,
  `NumeroPuerta` int NOT NULL,
  PRIMARY KEY (`TerrID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Terreno`
--

LOCK TABLES `Terreno` WRITE;
/*!40000 ALTER TABLE `Terreno` DISABLE KEYS */;
/*!40000 ALTER TABLE `Terreno` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `UnidadHabitacional`
--

DROP TABLE IF EXISTS `UnidadHabitacional`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `UnidadHabitacional` (
  `UnidadID` int NOT NULL AUTO_INCREMENT,
  `TerrID` int NOT NULL,
  `Estado` varchar(30) NOT NULL,
  `Piso` int NOT NULL,
  PRIMARY KEY (`UnidadID`),
  KEY `TerrID` (`TerrID`),
  CONSTRAINT `UnidadHabitacional_ibfk_1` FOREIGN KEY (`TerrID`) REFERENCES `Terreno` (`TerrID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `UnidadHabitacional`
--

LOCK TABLES `UnidadHabitacional` WRITE;
/*!40000 ALTER TABLE `UnidadHabitacional` DISABLE KEYS */;
/*!40000 ALTER TABLE `UnidadHabitacional` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reservasalon`
--

DROP TABLE IF EXISTS `reservasalon`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reservasalon` (
  `ReservaID` int NOT NULL AUTO_INCREMENT,
  `SalonID` int NOT NULL,
  `HabID` int NOT NULL,
  `Fecha` date NOT NULL,
  `HoraInicio` time NOT NULL,
  `HoraFin` time NOT NULL,
  `Comentario` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`ReservaID`),
  KEY `SalonID` (`SalonID`),
  KEY `HabID` (`HabID`),
  CONSTRAINT `reservasalon_ibfk_1` FOREIGN KEY (`SalonID`) REFERENCES `SalonComunal` (`SalonID`) ON DELETE CASCADE,
  CONSTRAINT `reservasalon_ibfk_2` FOREIGN KEY (`HabID`) REFERENCES `Habitante` (`HABID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reservasalon`
--

LOCK TABLES `reservasalon` WRITE;
/*!40000 ALTER TABLE `reservasalon` DISABLE KEYS */;
/*!40000 ALTER TABLE `reservasalon` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-11-06  3:00:01
