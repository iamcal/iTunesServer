-- --------------------------------------------------------

--
-- Table structure for table `playlists`
--

CREATE TABLE IF NOT EXISTS `playlists` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `date_create` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user` (`user`,`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `playlist_tracks`
--

CREATE TABLE IF NOT EXISTS `playlist_tracks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `playlist_id` int(10) unsigned NOT NULL,
  `track_id` int(10) unsigned NOT NULL,
  `in_order` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `playlist_id` (`playlist_id`,`in_order`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `plays`
--

CREATE TABLE IF NOT EXISTS `plays` (
  `user` varchar(255) NOT NULL,
  `track_key` varchar(255) NOT NULL,
  `num` int(10) unsigned NOT NULL,
  PRIMARY KEY (`user`,`track_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `tracks`
--

CREATE TABLE IF NOT EXISTS `tracks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `artist` varchar(255) NOT NULL,
  `album` varchar(255) NOT NULL,
  `year` varchar(255) NOT NULL,
  `num` tinyint(3) unsigned NOT NULL,
  `track` varchar(255) NOT NULL,
  `file` varchar(255) NOT NULL,
  `last_seen` int(10) unsigned NOT NULL,
  `last_scanned` int(10) unsigned NOT NULL,
  `updated` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `file` (`file`),
  KEY `artist` (`artist`,`album`,`num`,`track`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
