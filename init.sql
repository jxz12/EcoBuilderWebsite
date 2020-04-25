CREATE TABLE IF NOT EXISTS players (
    username VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    team TINYINT(1) NOT NULL,
    reverse_drag TINYINT(1) NOT NULL,
    age INT(11) NOT NULL DEFAULT -1,
    gender INT(11) NOT NULL DEFAULT -1,
    education INT(11) NOT NULL DEFAULT -1,
    newsletter TINYINT(1) DEFAULT 0,
    registered_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (username),
    UNIQUE KEY recovery (email)
);

CREATE TABLE IF NOT EXISTS playthroughs (
    play_id INT UNSIGNED AUTO_INCREMENT,
    username VARCHAR(255), -- nullable because we want to be able to keep data on delete
    level_index INT(11) UNSIGNED NOT NULL,
    score BIGINT(20) UNSIGNED NOT NULL,

    datetime_ticks BIGINT(20),
    matrix TEXT,
    actions TEXT,
    received_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- in case datetime_ticks is wrong

    PRIMARY KEY (play_id),
    FOREIGN KEY (username) REFERENCES players(username) ON DELETE SET NULL -- set to NULL for GDPR, but keep data
);

CREATE TABLE IF NOT EXISTS leaderboards ( -- this will have only scores with verified playthroughs
    username VARCHAR(255) NOT NULL, -- not nullable because we will remove if deleted
    level_index INT(11) NOT NULL,
    score BIGINT(20) NOT NULL, 
    play_id INT UNSIGNED,

    PRIMARY KEY (username, level_index),
    UNIQUE KEY (level_index, score, play_id),
    FOREIGN KEY (play_id) REFERENCES playthroughs(play_id) ON DELETE CASCADE, -- in case a playthrough is unverified then removed
    FOREIGN KEY (username) REFERENCES players(username) ON DELETE CASCADE -- for GDPR, cascade needed as username will not be deleted in playthroughs
);

CREATE TABLE IF NOT EXISTS highscores ( -- this store user high scores (REGARDLESS OF IF A PLAYTHROUGH EXISTS)
    username VARCHAR(255) NOT NULL, -- not nullable because we will remove if deleted
    level_index INT(11) NOT NULL,
    highscore BIGINT(20) NOT NULL,

    PRIMARY KEY (username, level_index),
    FOREIGN KEY (username) REFERENCES players(username) ON DELETE CASCADE -- cascade so that no hanging references
);

CREATE TABLE IF NOT EXISTS medians (
    level_index INT(11) NOT NULL,
    num_scores INT(11) NOT NULL DEFAULT 0,
    median_score BIGINT(20) NOT NULL DEFAULT 0,
    is_cached TINYINT(1) DEFAULT 0, -- set to false when a playthrough is uploaded, so that median calculated only once JIT

    PRIMARY KEY (level_index)
);

CREATE TABLE IF NOT EXISTS password_reset (
    username VARCHAR(255) NOT NULL,
    selector CHAR(16) NOT NULL,
    token CHAR(64) NOT NULL,
    expires BIGINT(20) NOT NULL,

    PRIMARY KEY (username),
    FOREIGN KEY (username) REFERENCES players(username) ON DELETE CASCADE
);