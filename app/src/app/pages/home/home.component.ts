import { Component } from '@angular/core';
import { FileService } from '../../../services/file';

@Component({
  selector: 'app-home',
  standalone: true,
  imports: [],
  templateUrl: './home.component.html',
  styleUrl: './home.component.scss'
})
export class HomeComponent {

  constructor(private fileService: FileService) { }

  // ngOnInit() {
  //   this.fileService.getDownloadUrl(4).subscribe(
  //     (data) => {
  //       console.log(data)
  //     },
  //     (error) => {
  //       console.log('Error ao realizar upload:', error)
  //     }
  //   )
  // }

  onFileSelected(event: Event): void {
    const input = event.target as HTMLInputElement;

    if (input.files && input.files.length > 0) {
      const file = input.files[0];
      const name = file.name;
      const type = file.type;
      const size = file.size;

      this.fileService.getUploadUrl(name, type, size).subscribe(
        (data) => {
          console.log(data)
        },
        (error) => {
          console.log('Error ao realizar upload:', error)
        }
      )
    }
  }
}
