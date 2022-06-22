-- MariaDB dump 10.19  Distrib 10.4.21-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: m151_db_tobias_odermatt
-- ------------------------------------------------------
-- Server version	10.4.21-MariaDB

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
-- Table structure for table `category`
--
CREATE DATABASE ToDo;
DROP TABLE IF EXISTS `category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `category` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(25) NOT NULL,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `category`
--

LOCK TABLES `category` WRITE;
/*!40000 ALTER TABLE `category` DISABLE KEYS */;
INSERT INTO `category` VALUES (1,'Haushalt'),(2,'IMS'),(3,'Administration');
/*!40000 ALTER TABLE `category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `to_do`
--

DROP TABLE IF EXISTS `to_do`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `to_do` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `priority` int(11) DEFAULT NULL,
  `title` varchar(45) NOT NULL,
  `text` varchar(300) DEFAULT NULL,
  `addDate` date DEFAULT NULL,
  `finishDate` date DEFAULT NULL,
  `archieve` enum('true','false') DEFAULT NULL,
  `category_category_id` int(11) NOT NULL,
  `status` tinyint(4) DEFAULT NULL,
  `user_ID` int(11) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `fk_To-Do_category1` (`category_category_id`),
  KEY `fk_To-Do_user1` (`user_ID`),
  CONSTRAINT `fk_To-Do_category1` FOREIGN KEY (`category_category_id`) REFERENCES `category` (`category_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_To-Do_user1` FOREIGN KEY (`user_ID`) REFERENCES `user` (`ID`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `to_do`
--

LOCK TABLES `to_do` WRITE;
/*!40000 ALTER TABLE `to_do` DISABLE KEYS */;
INSERT INTO `to_do` VALUES (1,5,'Modularbeit abgeben','Die Modularbeit soll zusammen mit dem DB Backup bis 22.00 Uhr abgeben werden','2022-02-25','2022-02-25','false',2,97,2),(2,1,'Steuererklärung machen','Die Steuererklärung muss in den nächsten 2 Wochen gemacht werden.','2022-02-25','2022-03-11','false',3,14,2);
/*!40000 ALTER TABLE `to_do` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `role` enum('user','admin') NOT NULL,
  `username` varchar(30) NOT NULL,
  `password` varchar(255) NOT NULL,
  `firstname` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` VALUES (1,'admin','Administrator','$2y$10$.U7ijc1UA0f0DP.2IY5HseozkAqIPbjUV.czgOH3xz6bpNmtK6kF6','admin','admin'),(2,'user','Steffan','$2y$10$QxBkNJTxplt4LiI6xiGzH.pkpO1ZR3MqPO/zVR5KeWNa7hJIby/Ci','Steffan','Luber');
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_has_categories`
--

DROP TABLE IF EXISTS `user_has_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_has_categories` (
  `user_ID` int(11) NOT NULL,
  `category_category_id` int(11) NOT NULL,
  PRIMARY KEY (`user_ID`,`category_category_id`),
  KEY `fk_user_has_category_category1` (`category_category_id`),
  CONSTRAINT `fk_user_has_category_category1` FOREIGN KEY (`category_category_id`) REFERENCES `category` (`category_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_user_has_category_user` FOREIGN KEY (`user_ID`) REFERENCES `user` (`ID`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_has_categories`
--

LOCK TABLES `user_has_categories` WRITE;
/*!40000 ALTER TABLE `user_has_categories` DISABLE KEYS */;
INSERT INTO `user_has_categories` VALUES (2,2),(2,3);
/*!40000 ALTER TABLE `user_has_categories` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2022-02-25 21:50:26
