<?php

namespace Model;

use ImageBoard\Board;
use ImageBoard\Post;
use ImageBoard\Thread;
use Site\User;

interface IModel
{
  // Board functions


  function getBoards():array;

  function getBoard(string $shortname):Board;

  function getNumberOfThreads(Board $board):int;

  function getNumberOfPosts(Board $board):int;

  // Thread functions

  function getThread(Board $board, int $id):Thread;

  function getPageOfThreads(Board $board, int $pageNo):array;

  function getCatalog(Board $board):array;

  // Post functions

  function getPost(Board $board, int $id):Post;

  function getAllPosts(Thread $t):array;

  function getUser(string $username, string $password):User;

  function getActiveMedia(Board $board):array;
}
