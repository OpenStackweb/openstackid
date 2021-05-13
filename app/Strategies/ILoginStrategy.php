<?php namespace Strategies;
/**
 * Interface ILoginStrategy
 * @package Strategies
 */
interface ILoginStrategy
{
    /**
     * @return mixed
     */
    public function  getLogin();

    /**
     * @param array $params
     * @return mixed
     */
    public function  postLogin(array $params = []);

    /**
     * @return mixed
     */
    public function  cancelLogin();

    /**
     * @param array $params
     * @return mixed
     */
    public function errorLogin(array $params);
} 