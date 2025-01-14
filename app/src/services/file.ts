import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class FileService {

  private baseUrl = 'http://localhost:8000';
  private uploadUrl = '/upload'
  private downloadUrl = '/download/:id'

  constructor(private http: HttpClient) { }

  // return { signedUlr: string, id: int}
  getUploadUrl(name: string, type: string, size: Number): Observable<any> {
    const url = this.baseUrl.concat(this.uploadUrl)

    return this.http.post(url, {name, type, size})
  }

  // return { signedUlr: string }
  getDownloadUrl(id: Number): Observable<any> {
    const url = this.baseUrl.concat(this.downloadUrl).replace(':id', `${id}`)

    return this.http.get(url)
  }
}
