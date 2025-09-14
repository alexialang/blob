import { ComponentFixture, TestBed } from '@angular/core/testing';
import { SlideButtonComponent } from './slide-button.component';

describe('SlideButtonComponent', () => {
  let component: SlideButtonComponent;
  let fixture: ComponentFixture<SlideButtonComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [SlideButtonComponent],
    }).compileComponents();

    fixture = TestBed.createComponent(SlideButtonComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should have default values', () => {
    expect(component.label).toBe('Button');
    expect(component.duration).toBe(0.4);
    expect(component.angle).toBe(5.7);
    expect(component.backgroundColor).toBe('#fff');
    expect(component.textColor).toBe('#000');
    expect(component.negative).toBe(false);
    expect(component.type).toBe('button');
  });

  it('should emit button click', () => {
    spyOn(component.buttonClick, 'emit');
    component.onClick();
    expect(component.buttonClick.emit).toHaveBeenCalled();
  });

  it('should calculate final background color when negative', () => {
    component.negative = true;
    expect(component.finalBackgroundColor).toBe('#000');
    
    component.negative = false;
    expect(component.finalBackgroundColor).toBe('#fff');
  });

  it('should calculate final button background when negative', () => {
    component.negative = true;
    expect(component.finalButtonBackground).toBe('#fff');
    
    component.negative = false;
    expect(component.finalButtonBackground).toBe('transparent');
  });

  it('should calculate final border color when negative', () => {
    component.negative = true;
    expect(component.finalBorderColor).toBe('#000');
    
    component.negative = false;
    expect(component.finalBorderColor).toBe('#fff');
  });

  it('should calculate final white text color when negative', () => {
    component.negative = true;
    expect(component.finalWhiteTextColor).toBe('#000');
    
    component.negative = false;
    expect(component.finalWhiteTextColor).toBe('#fff');
  });

  it('should calculate final black text color when negative', () => {
    component.negative = true;
    expect(component.finalBlackTextColor).toBe('#fff');
    
    component.negative = false;
    expect(component.finalBlackTextColor).toBe('#000');
  });
});