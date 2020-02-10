<?php

namespace Drupal\la_pills_rest\Plugin\rest\resource;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

trait FromAndUntilRestResourceTrait {

  /**
   * Checks if query string has a parameter present.
   * @param  Request $request
   *   Request object.
   * @param  string  $param
   *   Param name.
   *
   * @return boolean
   *   Present in query string or not.
   */
  private function hasQueryParam(Request $request, string $param) : bool {
    return $request->query->has($param);
  }

  /**
   * Checks if query string has a from parameter.
   *
   * @param  Request $request
   *   Request object.
   *
   * @return boolean
   *   Present in query string or not.
   */
  private function hasFromParam(Request $request) : bool {
    return $this->hasQueryParam($request, 'from');
  }

  /**
   * Checks if query string has an until parameter.
   *
   * @param  Request $request
   *   Request object.
   *
   * @return boolean
   *   Present in query string or not.
   */
  private function hasUntilParam(Request $request) : bool {
    return $this->hasQueryParam($request, 'until');
  }

  /**
   * Returns a timestamp created from request parameter using strtotime().
   *
   * @param  Request $request
   *   Request object.
   * @param  string  $param
   *   Param name.
   *
   * @return mixed
   *   Integer if conversion is successful, FALSE otherwise.
   */
  private function timestampFromQueryParam(Request $request, string $param) {
    return strtotime($request->query->get($param));
  }

  /**
   * Returns timestamp based on from parameter.
   *
   * @param  Request $request
   *   Request object.
   *
   * @return mixed
   *   Integer if conversion is successful, FALSE otherwise.
   */
  private function fromTimestamp(Request $request) {
    return $this->timestampFromQueryParam($request, 'from');
  }

  /**
   * Returns timestamp based on until parameter.
   *
   * @param  Request $request
   *   Request object.
   *
   * @return mixed
   *   Integer if conversion is successful, FALSE otherwise.
   */
  private function untilTimestamp(Request $request) {
    return $this->timestampFromQueryParam($request, 'until');
  }

  /**
   * Validates from and until parameters and throws BadRequestHttpException if
   * any is present but can not be converted to timestamp or from timestamp
   * value is less than until.
   *
   * @param  Request $request
   *   Request object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   Throws exception expected.
   */
  private function validateFromAndUntil(Request $request) : void {
    if ($this->hasFromParam($request)) {
      $from = $this->fromTimestamp($request);

      if ($from === FALSE) {
        throw new BadRequestHttpException('Malformed from parameter!');
      }
    }

    if ($this->hasUntilParam($request)) {
      $until = $this->untilTimestamp($request);

      if ($until === FALSE) {
        throw new BadRequestHttpException('Malformed until parameter!');
      }
    }

    if ($from && $until) {
      if ($from > $until) {
        throw new BadRequestHttpException('From parameter value is less than until!');
      }
    }
  }

}
