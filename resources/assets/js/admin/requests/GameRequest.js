import BaseRequest from '../lib/BaseRequest'

export default class GameRequest extends BaseRequest {
  getGames(params) {
    let url = '/admin/api/game';
    return this.get(url, params);
  }

  detailGame(id) {
    let url = `/admin/api/game/${id}/edit`;
    return this.get(url);
  }

  createGame(game) {
  	let url = '/admin/api/game/create';
  	return this.post(url, game);
  }

  updateGame(game) {
  	let url = '/admin/api/game/update';
  	return this.post(url, game);
  }

  deleteGame(gameId) {
  	let url = `/admin/api/game/${gameId}/delete`;
  	return this.post(url);
  }

  orderGames () {
    let url = `/admin/api/game/order`;
    return this.put(url);
  }
}
