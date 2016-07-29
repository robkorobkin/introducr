-- phpMyAdmin SQL Dump
-- version 3.3.9.2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Dec 17, 2015 at 06:40 PM
-- Server version: 5.5.9
-- PHP Version: 5.3.6

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `zocalo`
--

-- --------------------------------------------------------

--
-- Table structure for table `checkins`
--

CREATE TABLE `checkins` (
  `checkinid` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `time` datetime NOT NULL,
  `lat` float NOT NULL,
  `lon` float NOT NULL,
  `location` varchar(255) NOT NULL,
  PRIMARY KEY (`checkinid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=5 ;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `messageId` int(11) NOT NULL AUTO_INCREMENT,
  `senderId` int(11) NOT NULL,
  `targetId` int(11) NOT NULL,
  `content` text NOT NULL,
  `messageDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `isRead` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`messageId`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;

-- --------------------------------------------------------

--
-- Table structure for table `relationships`
--

CREATE TABLE `relationships` (
  `relationshipId` int(11) NOT NULL AUTO_INCREMENT,
  `selfId` int(11) NOT NULL,
  `otherId` int(11) NOT NULL,
  `status` varchar(255) NOT NULL,
  `isFriend` int(11) NOT NULL,
  `isBlocked` int(11) NOT NULL,
  `hasBlocked` int(11) NOT NULL,
  `numUnread` int(11) NOT NULL,
  `lastMessageDate` datetime NOT NULL,
  `lastCheckedDate` datetime NOT NULL,
  PRIMARY KEY (`relationshipId`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=12 ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `uid` int(11) NOT NULL AUTO_INCREMENT,
  `fbid` bigint(20) NOT NULL,
  `isActive` int(11) NOT NULL,
  `isNew` int(11) NOT NULL DEFAULT '1',
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `city` varchar(255) NOT NULL,
  `state` varchar(63) NOT NULL,
  `birthday` datetime NOT NULL,
  `gender` varchar(15) NOT NULL,
  `dateCreated` datetime NOT NULL,
  `dateAccessed` datetime NOT NULL,
  `dateModified` datetime NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_IsPublic` int(11) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `phone_IsPublic` int(11) NOT NULL,
  `bio` text NOT NULL,
  PRIMARY KEY (`uid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=35 ;
