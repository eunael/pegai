import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
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

  uploadFile(file: File, signedUrl: string): Observable<any> {
    const headers = new HttpHeaders({
      'Content-Type': file.type, // Define o tipo do arquivo corretamente
    });

    return this.http.put(signedUrl, file, { headers, reportProgress: true, observe: 'events' });
  }
}
